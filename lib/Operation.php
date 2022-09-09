<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\FilesAutomatedTagging;

use InvalidArgumentException;
use OCA\FilesAutomatedTagging\AppInfo\Application;
use OCA\GroupFolders\Mount\GroupFolderStorage;
use OCA\WorkflowEngine\Entity\File;
use OCP\EventDispatcher\Event;
use OCP\Files\IHomeStorage;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Storage\IStorage;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
use OCP\WorkflowEngine\IComplexOperation;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IRuleMatcher;
use OCP\WorkflowEngine\ISpecificOperation;
use RuntimeException;
use UnexpectedValueException;

class Operation implements ISpecificOperation, IComplexOperation {
	protected ISystemTagObjectMapper $objectMapper;
	protected ISystemTagManager $tagManager;
	protected IManager $checkManager;
	protected IL10N $l;
	private IConfig $config;
	private IURLGenerator $urlGenerator;
	private IMountManager $mountManager;

	public function __construct(
		ISystemTagObjectMapper $objectMapper,
		ISystemTagManager $tagManager,
		IManager $checkManager,
		IL10N $l,
		IConfig $config,
		IURLGenerator $urlGenerator,
		IMountManager $mountManager
	) {
		$this->objectMapper = $objectMapper;
		$this->tagManager = $tagManager;
		$this->checkManager = $checkManager;
		$this->l = $l;
		$this->config = $config;
		$this->urlGenerator = $urlGenerator;
		$this->mountManager = $mountManager;
	}

	public function checkOperations(IStorage $storage, int $fileId, string $file): void {
		$matcher = $this->checkManager->getRuleMatcher();
		$matcher->setFileInfo($storage, $file);
		$matcher->setOperation($this);
		$matches = $matcher->getFlows(false);

		foreach ($matches as $match) {
			$this->objectMapper->assignTags($fileId, 'files', explode(',', $match['operation']));
		}
	}

	/**
	 * @throws UnexpectedValueException
	 */
	public function validateOperation(string $name, array $checks, string $operation): void {
		if ($operation === '') {
			throw new UnexpectedValueException($this->l->t('No tags given'));
		}

		$systemTagIds = explode(',', $operation);
		try {
			$this->tagManager->getTagsByIds($systemTagIds);
		} catch (TagNotFoundException $e) {
			throw new UnexpectedValueException($this->l->t('Tag(s) could not be found: %s', implode(', ', $e->getMissingTags())));
		} catch (InvalidArgumentException $e) {
			throw new UnexpectedValueException($this->l->t('At least one of the given tags is invalid'));
		}
	}

	public function isTaggingPath(IStorage $storage, string $file): bool {
		if ($storage->instanceOfStorage(GroupFolderStorage::class)) {
			// We do not tag the roots of groupfolders, but every path inside we do
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
			[$folder, $subPath] = explode('/', $file, 2);
			// the root folder only contains appdata and home mounts
			// anything in a non homestorage and not in the appdata folder
			// should be a mounted folder
			return $folder !== $this->getAppDataFolderName() && substr_count($subPath, '/') >= 1;
		}
	}

	private function getAppDataFolderName(): string {
		$instanceId = $this->config->getSystemValue('instanceid', null);
		if ($instanceId === null) {
			throw new RuntimeException('no instance id!');
		}

		return 'appdata_' . $instanceId;
	}

	public function getDisplayName(): string {
		return $this->l->t('Automated tagging');
	}

	public function getDescription(): string {
		return $this->l->t('Automated tagging of files');
	}

	public function getIcon(): string {
		return $this->urlGenerator->imagePath(Application::APPID, 'app.svg');
	}

	public function isAvailableForScope(int $scope): bool {
		return true;
	}

	public function onEvent(string $eventName, Event $event, IRuleMatcher $ruleMatcher): void {
		// Assigning tags is handled though the cache listener
	}

	public function getEntityId(): string {
		return File::class;
	}

	public function getTriggerHint(): string {
		return $this->l->t('File is changed');
	}
}
