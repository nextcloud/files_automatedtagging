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

namespace OCA\FilesAutomatedTagging\Tests;

use OC\Files\Storage\Home;
use OC\Files\Storage\Local;
use OCA\FilesAutomatedTagging\Operation;
use OCP\Files\Storage\IStorage;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\WorkflowEngine\IManager;
use OCP\WorkflowEngine\IRuleMatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class OperationTest extends TestCase {

	/** @var ISystemTagObjectMapper|MockObject */
	protected $objectMapper;
	/** @var ISystemTagManager|MockObject */
	protected $tagManager;
	/** @var IManager|MockObject */
	protected $checkManager;
	/** @var IL10N|MockObject */
	protected $l;
	/** @var IConfig|MockObject */
	protected $config;
	/** @var Operation */
	protected $operation;
	/** @var IURLGenerator|MockObject */
	protected $urlGenerator;
	/** @var IRuleMatcher|MockObject */
	protected $ruleMatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->ruleMatcher = $this->createMock(IRuleMatcher::class);

		$this->objectMapper = $this->createMock(ISystemTagObjectMapper::class);

		$this->tagManager = $this->createMock(ISystemTagManager::class);

		$this->checkManager = $this->createMock(IManager::class);
		$this->checkManager->expects($this->any())
			->method('getRuleMatcher')
			->willReturn($this->ruleMatcher);

		$this->l = $this->createMock(IL10N::class);

		$this->config = $this->createMock(IConfig::class);
		$this->config->method('getSystemValue')
			->willReturn('instanceid');

		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->operation = new Operation(
			$this->objectMapper, $this->tagManager, $this->checkManager, $this->l, $this->config, $this->urlGenerator
		);
	}

	protected function getStorageMock() {
		return $this->createMock(IStorage::class);
	}

	public function dataCheckOperations() {
		return [
			[$this->getStorageMock(), 123, 'path', [], []],
			[$this->getStorageMock(), 42, 'path2', [['operation' => '2']], [
				[2],
			]],
			[$this->getStorageMock(), 23, 'path2', [
				['operation' => '2,3'],
				['operation' => '42']
			], [
				[2, 3],
				[42],
			]],
		];
	}

	/**
	 * @dataProvider dataCheckOperations
	 *
	 * @param IStorage $storage
	 * @param int $fileId
	 * @param string $file
	 * @param array[] $matches
	 * @param array[] $expected
	 */
	public function testCheckOperations(IStorage $storage, $fileId, $file, array $matches, array $expected) {
		$this->ruleMatcher->expects($this->once())
			->method('setFileInfo')
			->with($storage, $file);
		$this->ruleMatcher->expects($this->once())
			->method('getMatchingOperations')
			->with(Operation::class, false)
			->willReturn($matches);

		foreach ($expected as $key => $tags) {
			$this->objectMapper->expects($this->at($key))
				->method('assignTags')
				->with($fileId, 'files', $tags);
		}

		$this->operation->checkOperations($storage, $fileId, $file);
	}

	public function taggingPathDataProvider() {
		return [
			[Home::class, 'trash/foo', false],
			[Home::class, 'files/foo', true],
			[Home::class, 'files', false],
			[Local::class, 'foo', true],
			[Local::class, 'appdata_instanceid/foo', false],
		];
	}

	/**
	 * @dataProvider taggingPathDataProvider
	 * @param string $storageClass
	 * @param string $path
	 * @param bool $expected
	 */
	public function testIsTaggingPath(string $storageClass, string $path, bool $expected) {
		/** @var IStorage|MockObject $storage */
		$storage = $this->getMockBuilder($storageClass)
			->disableOriginalConstructor()
			->setMethodsExcept(['instanceOfStorage'])
			->getMock();
		$this->assertEquals($expected, $this->operation->isTaggingPath($storage, $path));
	}
}
