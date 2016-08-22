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

use OCA\FilesAutomatedTagging\AppInfo\Application;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Settings\ISettings;
use OCP\Util;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Admin implements ISettings {

	/** @var IL10N */
	private $l10n;

	/** @var string */
	private $appName;

	/** @var EventDispatcherInterface */
	private $eventDispatcher;

	/**
	 * @param string $appName
	 * @param IL10N $l
	 * @param EventDispatcherInterface $eventDispatcher
	 */
	public function __construct($appName, IL10N $l, EventDispatcherInterface $eventDispatcher) {
		$this->appName = $appName;
		$this->l10n = $l;
		$this->eventDispatcher = $eventDispatcher;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$this->eventDispatcher->dispatch('OCP\WorkflowEngine::loadAdditionalSettingScripts');
		Util::addScript($this->appName, 'admin');
		$parameters = [
			'appid' => $this->appName,
			'heading' => $this->l10n->t('Files automated tagging'),
			'description' => $this->l10n->t('Each rule group consists of one or more rules. A request matches a group if all rules evaluate to true. On uploading a file all defined groups are evaluated and when matching, the given collaborative tags are assigned to the file.'),
		];

		return new TemplateResponse('workflowengine', 'admin', $parameters, 'blank');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'workflow';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 75;
	}

}
