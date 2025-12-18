<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAutomatedTagging\AppInfo;

use OCA\FilesAutomatedTagging\Listener\CacheListener;
use OCA\FilesAutomatedTagging\Listener\RegisterFlowOperationsListener;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Files\Cache\CacheEntryInsertedEvent;
use OCP\Files\Cache\CacheEntryUpdatedEvent;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;

class Application extends App implements IBootstrap {
	public const APPID = 'files_automatedtagging';

	public function __construct() {
		parent::__construct(self::APPID);
	}

	#[\Override]
	public function register(IRegistrationContext $context): void {
		// While both Inserted and Updated are sometimes triggered, only Inserted is trigged when
		// a file/folder is created in a files_external mount externally and the user navigates to it
		$context->registerEventListener(CacheEntryInsertedEvent::class, CacheListener::class);
		$context->registerEventListener(CacheEntryUpdatedEvent::class, CacheListener::class);

		$context->registerEventListener(RegisterOperationsEvent::class, RegisterFlowOperationsListener::class);
	}

	#[\Override]
	public function boot(IBootContext $context): void {
	}
}
