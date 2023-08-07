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
use OCA\Files_External\Lib\Storage\SMB;
use OCA\FilesAutomatedTagging\Operation;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Storage\IStorage;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\TagNotFoundException;
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
	/** @var IMountManager|MockObject */
	protected $mountManager;
	/** @var IRootFolder|MockObject */
	protected $rootFolder;
	/** @var \OCA\WorkflowEngine\Entity\File|MockObject */
	protected $fileEntity;
	/** @var IUserSession|MockObject */
	protected $userSession;
	/** @var IGroupManager|MockObject */
	protected $groupManager;

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

		$this->mountManager = $this->createMock(IMountManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->fileEntity = $this->createMock(\OCA\WorkflowEngine\Entity\File::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupManager = $this->createMock(IGroupManager::class);

		$this->operation = new Operation(
			$this->objectMapper,
			$this->tagManager,
			$this->checkManager,
			$this->l,
			$this->config,
			$this->urlGenerator,
			$this->mountManager,
			$this->rootFolder,
			$this->fileEntity,
			$this->userSession,
			$this->groupManager
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
			->method('setEntitySubject');
		$this->ruleMatcher->expects($this->once())
			->method('setOperation')
			->with($this->operation);
		$this->ruleMatcher->expects($this->once())
			->method('getFlows')
			->with(false)
			->willReturn($matches);
		$node = $this->createMock(File::class);
		$this->rootFolder->expects($this->once())
			->method('getById')
			->with($fileId)
			->willReturn([$node]);

		$withConsecutive = [];
		foreach ($expected as $tags) {
			$withConsecutive[] = [$fileId, 'files', $tags];
		}

		foreach ($expected as $key => $tags) {
			$this->objectMapper->expects($this->any())
				->method('assignTags')
				->withConsecutive(...$withConsecutive);
		}

		$this->operation->checkOperations($storage, $fileId, $file);
	}

	public function dataValidateOperation() {
		$public = $this->createMock(ISystemTag::class);
		$public->method('isUserVisible')
			->willReturn(true);
		$public->method('isUserAssignable')
			->willReturn(true);
		$restricted = $this->createMock(ISystemTag::class);
		$restricted->method('isUserVisible')
			->willReturn(true);
		$restricted->method('isUserAssignable')
			->willReturn(false);
		$invisible = $this->createMock(ISystemTag::class);
		$invisible->method('isUserVisible')
			->willReturn(false);
		$invisible->method('isUserAssignable')
			->willReturn(false);

		return [
			['', null, false, 1],
			['1', [$public], false, null],
			['2,1', [$restricted, $public], false, 4],
			['1,3', [$public, $invisible], false, 4],
			['1', [$public], true, null],
			['2,1', [$restricted, $public], true, null],
			['1,3', [$public, $invisible], true, null],
			['4', new TagNotFoundException(), false, 2],
			['5', new \InvalidArgumentException(), false, 3],
		];
	}

	/**
	 * @dataProvider dataValidateOperation
	 *
	 * @param string $operation
	 * @param ISystemTag[]|\Exception|null $tags
	 * @param bool $isAdmin
	 * @param int|null $exceptionCode
	 */
	public function testValidateOperation(string $operation, $tags, bool $isAdmin, ?int $exceptionCode) {
		if ($tags === null) {
			$this->tagManager->expects($this->never())
				->method('getTagsByIds');
		} elseif (is_array($tags)) {
			$this->tagManager->expects($this->once())
				->method('getTagsByIds')
				->willReturn($tags);
		} else {
			$this->tagManager->expects($this->once())
				->method('getTagsByIds')
				->willThrowException($tags);
		}

		if ($isAdmin) {
			$userId = 'admin';
			$user = $this->createMock(IUser::class);
			$user->method('getUID')
				->willReturn($userId);
			$this->userSession->method('getUser')
				->willReturn($user);
			$this->groupManager->method('isAdmin')
				->with($userId)
				->willReturn(true);
		}

		if ($exceptionCode !== null) {
			$this->expectExceptionCode($exceptionCode);
		}

		$this->operation->validateOperation('', [], $operation);
	}

	public function taggingPathDataProvider() {
		$homeId = 'home::alice';
		$localId = 'local::/mnt/users/alice';
		$smbId = 'smb::alice@ser.vr/share/thing';
		$mountPoint = '/alice/files/NetworkDrive/';
		$userHomeMountPoint = '/alice/files/';
		return [
			[Home::class, $homeId, 'trash/foo', false],
			[Home::class, $homeId, 'files/foo', true],
			[Home::class, $homeId, 'files', false],
			[Local::class, $localId, '', false, $userHomeMountPoint],
			[Local::class, $localId, 'foo', true, $userHomeMountPoint],
			[Local::class, $localId, 'foo', true, $mountPoint],
			[Local::class, $localId, 'appdata_instanceid/foo', false],
			[SMB::class, $smbId, 'in-the-mountpoint.txt', true, $userHomeMountPoint],
			[SMB::class, $smbId, 'in-the-mountpoint.txt', true, $mountPoint],
			[SMB::class, $smbId, 'sub1/in-the-folder.md', true, $mountPoint],
			[SMB::class, $smbId, 'somewhere/deeply/nested/so-cozy.mp4', true, $userHomeMountPoint],
			[SMB::class, $smbId, 'somewhere/deeply/nested/so-cozy.mp4', true, $mountPoint],
			[SMB::class, $smbId, '', true, $mountPoint],
			[SMB::class, $smbId, '', false, $userHomeMountPoint],
		];
	}

	/**
	 * @dataProvider taggingPathDataProvider
	 * @param string $storageClass
	 * @param string $path
	 * @param bool $expected
	 */
	public function testIsTaggingPath(string $storageClass, string $storageId, string $path, bool $expected, string $mountPointPath = '') {
		$isLocal = $storageClass === Home::class || $storageClass === Local::class;

		/** @var IStorage|MockObject $storage */
		$storage = $this->getMockBuilder($storageClass)
			->disableOriginalConstructor()
			->setMethodsExcept(['instanceOfStorage'])
			->getMock();

		$storage->expects($this->any())
			->method('getId')
			->willReturn($storageId);
		$storage->expects($this->any())
			->method('isLocal')
			->willReturn($isLocal);

		$mountPoint = $this->createMock(IMountPoint::class);
		$mountPoint->expects($this->any())
			->method('getMountType')
			->willReturn($mountPointPath === '' ? '' : 'external');
		$mountPoint->expects($this->any())
			->method('getMountPoint')
			->willReturn($mountPointPath);

		$this->mountManager->expects($this->any())
			->method('findByStorageId')
			->willReturn([$mountPoint]);

		$this->assertEquals($expected, $this->operation->isTaggingPath($storage, $path));
	}
}
