<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @license GNU AGPL version 3 or any later version
 *
 * SPDX-FileCopyrightText: 2016 Joas Schilling <coding@schilljs.com>
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

	public function register(IRegistrationContext $context): void {
		$context->registerEventListener(CacheEntryInsertedEvent::class, CacheListener::class);
		$context->registerEventListener(CacheEntryUpdatedEvent::class, CacheListener::class);

		$context->registerEventListener(RegisterOperationsEvent::class, RegisterFlowOperationsListener::class);
	}

	public function boot(IBootContext $context): void {
	}
}
