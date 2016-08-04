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

use OCA\FilesAutomatedTagging\CacheWrapper;
use Test\TestCase;

class CacheWrapperTest extends TestCase {

	/** @var \OCP\Files\Cache\ICache|\PHPUnit_Framework_MockObject_MockObject */
	protected $cache;
	/** @var \OCP\Files\Storage\IStorage|\PHPUnit_Framework_MockObject_MockObject */
	protected $storage;
	/** @var \OCA\FilesAutomatedTagging\Operation|\PHPUnit_Framework_MockObject_MockObject */
	protected $operation;

	protected function setUp() {
		parent::setUp();

		$this->cache = $this->getMockBuilder('OCP\Files\Cache\ICache')
			->getMock();
		$this->storage = $this->getMockBuilder('OCP\Files\Storage\IStorage')
			->getMock();
		$this->operation = $this->getMockBuilder('OCA\FilesAutomatedTagging\Operation')
			->disableOriginalConstructor()
			->getMock();
	}

	public function dataInsert() {
		return [
			['/admin', '/files/file', ['data1'], 123, true],
			['/admin/files/externalstorage', '/file', ['data3'], 123, true],
			['/admin', '/files', ['data3'], 123, false],
			['/admin', '/file', ['data2'], -1, false],
			['/admin', '/cache/file', ['data3'], 123, false],
		];
	}

	/**
	 * @dataProvider dataInsert
	 *
	 * @param string $mountPoint
	 * @param string $path
	 * @param array $data
	 * @param int $fileId
	 * @param bool $checkOperations
	 */
	public function testInsert($mountPoint, $path, array $data, $fileId, $checkOperations) {
		$test = new CacheWrapper($this->cache, $this->storage, $this->operation, $mountPoint);

		$this->cache->expects($this->once())
			->method('insert')
			->with($path, $data)
			->willReturn($fileId);

		$this->operation->expects($checkOperations ? $this->once() : $this->never())
			->method('checkOperations')
			->with($this->storage, $fileId, $path);

		$test->insert($path, $data);
	}
}
