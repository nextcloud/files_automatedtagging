<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @license GNU AGPL version 3 or any later version
 *
 * SPDX-FileCopyrightText: 2020 Arthur Schiwon <blizzz@arthur-schiwon.de>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAutomatedTagging\Listener;

use OCA\FilesAutomatedTagging\AppInfo\Application;
use OCA\FilesAutomatedTagging\Operation;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;
use OCP\WorkflowEngine\Events\RegisterOperationsEvent;
use Psr\Container\ContainerInterface;

class RegisterFlowOperationsListener implements IEventListener {
	private ContainerInterface $container;

	public function __construct(ContainerInterface $container) {
		$this->container = $container;
	}

	public function handle(Event $event): void {
		if (!$event instanceof RegisterOperationsEvent) {
			return;
		}
		$operation = $this->container->get(Operation::class);
		$event->registerOperation($operation);
		Util::addScript(Application::APPID, 'files_automatedtagging-main');
	}
}
