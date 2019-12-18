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

use OCA\FilesAutomatedTagging\Operation;
use OCA\FilesAutomatedTagging\CacheListener;
use OCA\WorkflowEngine\Manager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Util;
use OCP\WorkflowEngine\IManager;
use Symfony\Component\EventDispatcher\GenericEvent;

class Application extends \OCP\AppFramework\App {

	public function __construct() {
		parent::__construct('files_automatedtagging');
	}

	/**
	 * Register all hooks and listeners
	 */
	public function registerHooksAndListeners() {
		/** @var CacheListener $cacheListener */
		$cacheListener = $this->getContainer()->query(CacheListener::class);
		$cacheListener->listen();

		\OC::$server->query(IEventDispatcher::class)->addListener(IManager::EVENT_NAME_REG_OPERATION, function (GenericEvent $event) {
			$operation = \OC::$server->query(Operation::class);
			$flowManager = $event->getSubject();
			if($flowManager instanceof Manager) {
				$flowManager->registerOperation($operation);
				Util::addScript('files_automatedtagging', 'files_automatedtagging');
			}
		});
	}
}
