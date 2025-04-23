<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FilesAutomatedTagging\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {
	public function __construct(
		private readonly string $appName,
		private readonly IL10N $l10n,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		Util::addScript($this->appName, 'admin');
		$parameters = [
			'appid' => $this->appName,
			'docs' => 'admin-files-automated-tagging',
			'heading' => $this->l10n->t('Automated tagging'),
			'settings-hint' => $this->l10n->t('Automatically tag files based on factors such as filetype, user group memberships, time and more.'),
			'description' => $this->l10n->t('Each rule group consists of one or more rules. A request matches a group if all rules evaluate to true. On uploading a file all defined groups are evaluated and when matching, the given collaborative tags are assigned to the file.'),
		];

		return new TemplateResponse('workflowengine', 'admin', $parameters, 'blank');
	}

	#[\Override]
	public function getSection(): string {
		return 'workflow';
	}

	#[\Override]
	public function getPriority(): int {
		return 75;
	}
}
