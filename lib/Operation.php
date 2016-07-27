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

namespace OCA\FilesAutomatedTagging;


use OCP\Files\Storage\IStorage;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\WorkflowEngine\IManager;

class Operation {

	/** @var ISystemTagObjectMapper */
	protected $objectMapper;

	/** @var IManager */
	protected $checkManager;

	/**
	 * @param ISystemTagObjectMapper $objectMapper
	 * @param IManager $checkManager
	 */
	public function __construct(ISystemTagObjectMapper $objectMapper, IManager $checkManager) {
		$this->objectMapper = $objectMapper;
		$this->checkManager = $checkManager;
	}

	/**
	 * @param IStorage $storage
	 * @param int $fileId
	 * @param string $file
	 */
	public function checkOperations(IStorage $storage, $fileId, $file) {
		$this->checkManager->setFileInfo($storage, $file);
		$matches = $this->checkManager->getMatchingOperations('OCA\FilesAutomatedTagging\Operation', false);

		foreach ($matches as $match) {
			$this->objectMapper->assignTags($fileId, 'files', json_decode($match['operation'], true));
		}
	}
}
