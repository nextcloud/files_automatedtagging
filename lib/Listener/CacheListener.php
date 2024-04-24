<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019 Robin Appelman <robin@icewind.nl>
 * @license GNU AGPL version 3 or any later version
 *
 * SPDX-FileCopyrightText: 2019 Robin Appelman <robin@icewind.nl>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAutomatedTagging\Listener;

use OCA\FilesAutomatedTagging\Operation;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Cache\AbstractCacheEvent;

class CacheListener implements IEventListener {
	private Operation $operation;

	public function __construct(Operation $operation) {
		$this->operation = $operation;
	}

	public function handle(Event $event): void {
		if (!$event instanceof AbstractCacheEvent) {
			return;
		}
		if ($this->operation->isTaggingPath($event->getStorage(), $event->getPath())) {
			$this->operation->checkOperations($event->getStorage(), $event->getFileId(), $event->getPath());
		}
	}
}
