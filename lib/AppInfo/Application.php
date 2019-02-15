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

namespace OCA\FilesAutomatedTagging\AppInfo;

use OCP\Files\Cache\CacheInsertEvent;
use OCA\FilesAutomatedTagging\Operation;
use OCP\Files\IHomeStorage;
use OCP\Files\Storage\IStorage;

class Application extends \OCP\AppFramework\App {

	public function __construct() {
		parent::__construct('files_automatedtagging');
	}

	/**
	 * Register all hooks and listeners
	 */
	public function registerHooksAndListeners() {
		$eventDispatcher = $this->getContainer()->getServer()->getEventDispatcher();

		$eventDispatcher->addListener(CacheInsertEvent::class, function (CacheInsertEvent $event) {
			/** @var Operation $operation */
			$operation = $this->getContainer()->query(Operation::class);
			if ($operation->isTaggingPath($event->getStorage(), $event->getPath())) {
				$operation->checkOperations($event->getStorage(), $event->getFileId(), $event->getPath());
			}
		});
	}
}
