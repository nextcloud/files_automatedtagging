<?php declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 Robin Appelman <robin@icewind.nl>
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
use OCP\Files\Cache\CacheUpdateEvent;
use OCP\Files\Cache\ICacheEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class CacheListener {
	private $eventDispatcher;
	private $operation;

	public function __construct(EventDispatcher $eventDispatcher, Operation $operation) {
		$this->eventDispatcher = $eventDispatcher;
		$this->operation = $operation;
	}

	public function listen() {
		$this->eventDispatcher->addListener(CacheInsertEvent::class, [$this, 'onCacheEvent']);
		$this->eventDispatcher->addListener(CacheUpdateEvent::class, [$this, 'onCacheEvent']);
	}

	public function onCacheEvent(ICacheEvent $event) {
		if ($this->operation->isTaggingPath($event->getStorage(), $event->getPath())) {
			$this->operation->checkOperations($event->getStorage(), $event->getFileId(), $event->getPath());
		}
	}
}
