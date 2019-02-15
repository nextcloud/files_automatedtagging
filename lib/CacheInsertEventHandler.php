<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use OCP\Files\Cache\CacheInsertEvent;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Storage\IStorage;

class CacheInsertEventHandler {

	/** @var Operation */
	private $operation;
	/** @var IMountManager */
	private $mountManager;

	public function __construct(Operation $operation, IMountManager $mountManager) {
		$this->operation = $operation;
		$this->mountManager = $mountManager;
	}

	public function handle(CacheInsertEvent $event) {
		if (!$this->isTaggingPath($event->getStorage(), $event->getPath())) {
			return;
		}

		$this->operation->checkOperations($event->getStorage(), $event->getFileId(), $event->getPath());
	}

	protected function isTaggingPath(IStorage $storage, string $path): bool {
		$mountPoints = $this->mountManager->findByStorageId($storage->getId());

		foreach ($mountPoints as $mountPoint) {
			$path = $mountPoint->getMountPoint() . $path;

			if (substr_count($path, '/') < 3) {
				break;
			}

			// '', admin, 'files', 'path/to/file.txt'
			list(,, $folder,) = explode('/', $path, 4);

			if ($folder === 'files') {
				return true;
			}
		}

		return false;


	}

}
