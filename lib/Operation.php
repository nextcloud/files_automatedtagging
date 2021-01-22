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

	/** @var ISystemTagObjectMapper */
	protected $objectMapper;

	/** @var ISystemTagManager */
	protected $tagManager;

	/** @var IManager */
	protected $checkManager;

	/** @var IL10N */
	protected $l;

	/** @var IConfig */
	private $config;

	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IMountManager */
	private $mountManager;

	/**
	 * @param ISystemTagObjectMapper $objectMapper
	 * @param ISystemTagManager $tagManager
	 * @param IManager $checkManager
	 * @param IL10N $l
	 * @param IConfig $config
	 */
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
	 * @param string $name
	 * @param array[] $checks
	 * @param string $operation
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

	/**
	 * returns a translated name to be presented in the web interface
	 *
	 * Example: "Automated tagging" (en), "Aŭtomata etikedado" (eo)
	 *
	 * @since 18.0.0
	 */
	public function getDisplayName(): string {
		return $this->l->t('Automated tagging');
	}

	/**
	 * returns a translated, descriptive text to be presented in the web interface.
	 *
	 * It should be short and precise.
	 *
	 * Example: "Tag based automatic deletion of files after a given time." (en)
	 *
	 * @since 18.0.0
	 */
	public function getDescription(): string {
		return $this->l->t('Automated tagging of files');
	}

	/**
	 * returns the URL to the icon of the operator for display in the web interface.
	 *
	 * Usually, the implementation would utilize the `imagePath()` method of the
	 * `\OCP\IURLGenerator` instance and simply return its result.
	 *
	 * Example implementation: return $this->urlGenerator->imagePath('myApp', 'cat.svg');
	 *
	 * @since 18.0.0
	 */
	public function getIcon(): string {
		return $this->urlGenerator->imagePath('files_automatedtagging', 'app.svg');
	}

	/**
	 * returns whether the operation can be used in the requested scope.
	 *
	 * Scope IDs are defined as constants in OCP\WorkflowEngine\IManager. At
	 * time of writing these are SCOPE_ADMIN and SCOPE_USER.
	 *
	 * For possibly unknown future scopes the recommended behaviour is: if
	 * user scope is permitted, the default behaviour should return `true`,
	 * otherwise `false`.
	 *
	 * @since 18.0.0
	 */
	public function isAvailableForScope(int $scope): bool {
		return true;
	}

	/**
	 * Is being called by the workflow engine when an event was triggered that
	 * is configured for this operation. An evaluation whether the event
	 * qualifies for this operation to run has still to be done by the
	 * implementor.
	 *
	 * If the implementor is an IComplexOperation, this method will not be
	 * called automatically. It can be used or left as no-op by the implementor.
	 *
	 * @since 18.0.0
	 */
	public function onEvent(string $eventName, Event $event, IRuleMatcher $ruleMatcher): void {
		// Assigning tags is handled though the cache listener
	}

	/**
	 * returns the id of the entity the operator is designed for
	 *
	 * Example: 'WorkflowEngine_Entity_File'
	 *
	 * @since 18.0.0
	 */
	public function getEntityId(): string {
		return File::class;
	}

	/**
	 * As IComplexOperation chooses the triggering events itself, a hint has
	 * to be shown to the user so make clear when this operation is becoming
	 * active. This method returns such a translated string.
	 *
	 * Example: "When a file is accessed" (en)
	 *
	 * @since 18.0.0
	 */
	public function getTriggerHint(): string {
		return $this->l->t('File is changed');
	}
}
