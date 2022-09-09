<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCA\FilesAutomatedTagging\Settings;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {

	private IL10N $l10n;
	private string $appName;

	public function __construct(string $appName, IL10N $l) {
		$this->appName = $appName;
		$this->l10n = $l;
	}

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

	public function getSection(): string {
		return 'workflow';
	}

	public function getPriority(): int {
		return 75;
	}
}
