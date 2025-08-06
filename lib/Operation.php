<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAutomatedTagging;

use InvalidArgumentException;
use OCA\FilesAutomatedTagging\AppInfo\Application;
use OCA\GroupFolders\Mount\GroupFolderStorage;
use OCA\WorkflowEngine\Entity\File;
use OCP\EventDispatcher\Event;
use OCP\Files\IHomeStorage;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Node;
use OCP\Files\Storage\IStorage;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use OCP\WorkflowEngine\IComplexOperation;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IRuleMatcher;
use OCP\WorkflowEngine\ISpecificOperation;
use Psr\Log\LoggerInterface;
use RuntimeException;
use UnexpectedValueException;

class Operation implements ISpecificOperation, IComplexOperation {
	protected array $issuedTagNotFoundWarnings = [];

	public function __construct(
		protected readonly ISystemTagObjectMapper $objectMapper,
		protected readonly ISystemTagManager $tagManager,
		protected readonly IManager $checkManager,
		protected readonly IL10N $l,
		protected readonly IConfig $config,
		protected readonly IURLGenerator $urlGenerator,
		protected readonly IMountManager $mountManager,
		protected readonly IRootFolder $rootFolder,
		protected readonly File $fileEntity,
		protected readonly IUserSession $userSession,
		protected readonly IGroupManager $groupManager,
		protected readonly LoggerInterface $logger,
	) {
	}

	public function checkOperations(IStorage $storage, int $fileId, string $file): void {
		$matcher = $this->checkManager->getRuleMatcher();
		$matcher->setFileInfo($storage, $file);
		$nodes = $this->rootFolder->getById($fileId);
		$node = current($nodes);
		if ($node instanceof Node) {
			$matcher->setEntitySubject($this->fileEntity, $node);
		}
		$matcher->setOperation($this);


		$matches = $matcher->getFlows(false);

		foreach ($matches as $match) {
			try {
				$this->objectMapper->assignTags((string)$fileId, 'files', explode(',', $match['operation']));
			} catch (TagNotFoundException $e) {
				$msg = sprintf('The tag to assign (ID %s) cannot be found anymore. The related rule is %s.',
					$match['operation'],
					$match['scope_type'] === 0 ? 'global' : 'owned by ' . $match['scope_actor_id']
				);
				if (isset($this->issuedTagNotFoundWarnings[md5($msg)])) {
					continue;
				}
				$this->issuedTagNotFoundWarnings[md5($msg)] = true;
				$this->logger->error($msg);
			}
		}
	}

	/**
	 * @throws UnexpectedValueException
	 */
	#[\Override]
	public function validateOperation(string $name, array $checks, string $operation): void {
		if ($operation === '') {
			throw new UnexpectedValueException($this->l->t('No tags given'), 1);
		}

		$systemTagIds = explode(',', $operation);
		try {
			$tags = $this->tagManager->getTagsByIds($systemTagIds);

			$user = $this->userSession->getUser();
			$isAdmin = $user instanceof IUser && $this->groupManager->isAdmin($user->getUID());

			if (!$isAdmin) {
				foreach ($tags as $tag) {
					if (!$tag->isUserAssignable() || !$tag->isUserVisible()) {
						throw new UnexpectedValueException($this->l->t('At least one of the given tags is invalid'), 4);
					}
				}
			}
		} catch (TagNotFoundException) {
			throw new UnexpectedValueException($this->l->t('At least one of the given tags is invalid'), 2);
		} catch (InvalidArgumentException) {
			throw new UnexpectedValueException($this->l->t('At least one of the given tags is invalid'), 3);
		}
	}

	public function isTaggingPath(IStorage $storage, string $file): bool {
		if ($storage->instanceOfStorage(GroupFolderStorage::class)) {
			// note: $storage only matches if group folder already exists, otherwise it's a local storage
			// with the group folder root on top.

			// $file can be "__groupfolders/$id" or a relative path inside it
			// We do not (re-)tag the roots of groupfolders, but every path inside we do
			return strpos($file, '__groupfolders') !== 0;
		}

		if (!$storage->isLocal() || strpos($storage->getId(), 'local::') === 0) {
			$mountPoints = $this->mountManager->findByStorageId($storage->getId());
			if (!empty($mountPoints) && $mountPoints[0]->getMountType() === 'external') {
				// it is OK to only look at the first one, if there are many
				if (!empty($file)) {
					// a file somewhere on the storage is always OK
					return true;
				}

				// external storages are always ok as long as not mounted as user root
				$mountPointPath = rtrim($mountPoints[0]->getMountPoint(), '/');
				$mountPointPieces = explode('/', $mountPointPath);
				$mountPointName = array_pop($mountPointPieces);
				// user root structure: /$USER_ID/files
				return ($mountPointName !== 'files' || count($mountPointPieces) !== 2);
			}
		}

		if (substr_count($file, '/') === 0) {
			return false;
		}

		if ($storage->instanceOfStorage(IHomeStorage::class)) {
			[$folder] = explode('/', $file, 2);
			return $folder === 'files';
		} else {
			[$folder, $subPath] = explode('/', $file, 3);
			// the root folder only contains appdata and home mounts
			// anything in a non homestorage and not in the appdata folder
			// should be a mounted folder
			return ($folder !== $this->getAppDataFolderName() && substr_count($subPath, '/') >= 1)
				// also match group folder root creation
				|| ($folder === '__groupfolders' && is_numeric($subPath));
		}
	}

	private function getAppDataFolderName(): string {
		$instanceId = $this->config->getSystemValue('instanceid', null);
		if ($instanceId === null) {
			throw new RuntimeException('no instance id!');
		}

		return 'appdata_' . $instanceId;
	}

	#[\Override]
	public function getDisplayName(): string {
		return $this->l->t('Automated tagging');
	}

	#[\Override]
	public function getDescription(): string {
		return $this->l->t('Automated tagging of files');
	}

	#[\Override]
	public function getIcon(): string {
		return $this->urlGenerator->imagePath(Application::APPID, 'app.svg');
	}

	#[\Override]
	public function isAvailableForScope(int $scope): bool {
		return in_array($scope, [
			IManager::SCOPE_ADMIN,
			IManager::SCOPE_USER,
		], true);
	}

	#[\Override]
	public function onEvent(string $eventName, Event $event, IRuleMatcher $ruleMatcher): void {
		// Assigning tags is handled though the cache listener
	}

	#[\Override]
	public function getEntityId(): string {
		return File::class;
	}

	#[\Override]
	public function getTriggerHint(): string {
		return $this->l->t('File is changed');
	}
}
