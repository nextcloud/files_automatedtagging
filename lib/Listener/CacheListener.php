<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAutomatedTagging\Listener;

use OCA\FilesAutomatedTagging\Operation;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\Cache\AbstractCacheEvent;

/**
 * @template-implements IEventListener<AbstractCacheEvent>
 */
class CacheListener implements IEventListener {
	public function __construct(
		private readonly Operation $operation,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!$event instanceof AbstractCacheEvent) {
			return;
		}

		if ($this->operation->isTaggingPath($event->getStorage(), $event->getPath())) {
			$this->operation->checkOperations($event->getStorage(), $event->getFileId(), $event->getPath());
		}
	}
}
