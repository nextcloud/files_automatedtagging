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

use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files\Storage\IStorage;

class StorageWrapper extends Wrapper {

	/** @var Operation */
	protected $operation;

	/** @var string */
	protected $mountPoint;

	/**
	 * @param array $parameters
	 */
	public function __construct($parameters) {
		parent::__construct($parameters);
		$this->operation = $parameters['operation'];
		$this->mountPoint = $parameters['mountPoint'];
	}

	/**
	 * get a cache instance for the storage
	 *
	 * @param string $path
	 * @param \OC\Files\Storage\Storage $storage
	 * @return \OC\Files\Cache\Cache
	 */
	public function getCache($path = '', $storage = null) {
		if (!$storage) {
			$storage = $this;
		}

		$cache = $this->storage->getCache($path, $storage);

		return new CacheWrapper($cache, $storage, $this->operation, $this->mountPoint);
	}

	/**
	 * see http://php.net/manual/en/function.file-put-contents.php
	 *
	 * @param string $path
	 * @param string $data
	 * @return bool
	 */
	public function file_put_contents($path, $data) {
		$result = $this->storage->file_put_contents($path, $data);
		if ($result !== false) {
			$this->checkOperationsOnPath($path);
		}
		return $result;
	}

	/**
	 * see http://php.net/manual/en/function.fopen.php
	 *
	 * @param string $path
	 * @param string $mode
	 * @return resource
	 */
	public function fopen($path, $mode) {
		$resource = $this->storage->fopen($path, $mode);
		if ($mode !== 'r' && $resource !== false) {
			$this->checkOperationsOnPath($path);
		}
		return $resource;
	}

	/**
	 * @param IStorage $sourceStorage
	 * @param string $sourceInternalPath
	 * @param string $targetInternalPath
	 * @return bool
	 */
	public function moveFromStorage(IStorage $sourceStorage, $sourceInternalPath, $targetInternalPath) {
		if ($sourceStorage === $this) {
			return $this->rename($sourceInternalPath, $targetInternalPath);
		}

		$this->checkOperationsOnPath($targetInternalPath);
		return $this->storage->moveFromStorage($sourceStorage, $sourceInternalPath, $targetInternalPath);
	}

	/**
	 * see http://php.net/manual/en/function.rename.php
	 *
	 * @param string $path1
	 * @param string $path2
	 * @return bool
	 */
	public function rename($path1, $path2) {
		// Renaming a part file (files/test.txt.ocTransferId1906445132.part => files/test.txt)
		// mean we update or upload a file.
		$p = strpos($path1, $path2);
		if ($p === 0) {
			$part = substr($path1, strlen($path2));
			//This is a rename of the transfer file to the original file
			if (strpos($part, '.ocTransferId') === 0) {
				$this->checkOperationsOnPath($path2);
			}
		}

		return $this->storage->rename($path1, $path2);
	}

	protected function checkOperationsOnPath(string $path) {
		if (!$this->isTaggingPath($path)) {
			return;
		}

		$fileId = $this->getCache()->getId($path);
		if ($fileId !== -1) {
			$this->operation->checkOperations($this, $fileId, $path);
		}
	}

	protected function isTaggingPath(string $path): bool {
		$path = $this->mountPoint . $path;

		if (substr_count($path, '/') < 3) {
			return false;
		}

		// '', admin, 'files', 'path/to/file.txt'
		list(,, $folder,) = explode('/', $path, 4);

		return $folder === 'files';
	}
}
