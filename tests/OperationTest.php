<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
use Psr\Log\LoggerInterface;
use Test\TestCase;

class OperationTest extends TestCase {
	protected ISystemTagObjectMapper&MockObject $objectMapper;
	protected ISystemTagManager&MockObject $tagManager;
	protected IManager&MockObject $checkManager;
	protected IL10N&MockObject $l;
	protected IConfig&MockObject $config;
	protected IURLGenerator&MockObject $urlGenerator;
	protected IRuleMatcher&MockObject $ruleMatcher;
	protected IMountManager&MockObject $mountManager;
	protected IRootFolder&MockObject $rootFolder;
	protected \OCA\WorkflowEngine\Entity\File&MockObject $fileEntity;
	protected IUserSession&MockObject $userSession;
	protected IGroupManager&MockObject $groupManager;
	protected LoggerInterface&MockObject $logger;
	protected Operation $operation;

	protected function setUp(): void {
		parent::setUp();

		$this->objectMapper = $this->createMock(ISystemTagObjectMapper::class);
		$this->tagManager = $this->createMock(ISystemTagManager::class);
		$this->checkManager = $this->createMock(IManager::class);
		$this->l = $this->createMock(IL10N::class);
		$this->config = $this->createMock(IConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->ruleMatcher = $this->createMock(IRuleMatcher::class);
		$this->mountManager = $this->createMock(IMountManager::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->fileEntity = $this->createMock(\OCA\WorkflowEngine\Entity\File::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->checkManager->expects($this->any())
			->method('getRuleMatcher')
			->willReturn($this->ruleMatcher);

		$this->config->method('getSystemValue')
			->willReturn('instanceid');

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
			$this->groupManager,
			$this->logger,
		);
	}

	public static function dataCheckOperations(): array {
		return [
			[123, 'path', [], []],
			[42, 'path2', [['operation' => '2']], [
				['2'],
			]],
			[23, 'path2', [
				['operation' => '2,3'],
				['operation' => '42']
			], [
				['2', '3'],
				['42'],
			]],
		];
	}

	/**
	 * @dataProvider dataCheckOperations
	 *
	 * @param array[] $matches
	 * @param array[] $expected
	 */
	public function testCheckOperations(int $fileId, string $file, array $matches, array $expected): void {
		$storage = $this->createMock(IStorage::class);

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
			$withConsecutive[] = [(string)$fileId, 'files', $tags];
		}

		$i = 0;
		$this->objectMapper->expects($this->exactly(count($withConsecutive)))
			->method('assignTags')
			->willReturnCallback(function () use ($withConsecutive, &$i) {
				$this->assertArrayHasKey($i, $withConsecutive);
				$this->assertSame($withConsecutive[$i], func_get_args());
				$i++;
			});

		$this->operation->checkOperations($storage, $fileId, $file);
	}

	public function createTagMock(bool $isUserVisible, bool $isUserAssignable): ISystemTag {
		$public = $this->createMock(ISystemTag::class);
		$public->method('isUserVisible')
			->willReturn($isUserVisible);
		$public->method('isUserAssignable')
			->willReturn($isUserAssignable);
		return $public;
	}

	public static function dataValidateOperation(): array {
		$public = ['isUserVisible' => true, 'isUserAssignable' => true];
		$restricted = ['isUserVisible' => true, 'isUserAssignable' => false];
		$invisible = ['isUserVisible' => false, 'isUserAssignable' => false];

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
	 */
	public function testValidateOperation(string $operation, array|\Throwable|null $tags, bool $isAdmin, ?int $exceptionCode): void {
		if ($tags === null) {
			$this->tagManager->expects($this->never())
				->method('getTagsByIds');
		} elseif (is_array($tags)) {
			$return = [];
			foreach ($tags as $tagData) {
				$return[] = $this->createTagMock(...$tagData);
			}

			$this->tagManager->expects($this->once())
				->method('getTagsByIds')
				->willReturn($return);
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

	public static function dataIsTaggingPath(): array {
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
	 * @dataProvider dataIsTaggingPath
	 */
	public function testIsTaggingPath(string $storageClass, string $storageId, string $path, bool $expected, string $mountPointPath = ''): void {
		$isLocal = $storageClass === Home::class || $storageClass === Local::class;

		/** @var IStorage&MockObject $storage */
		$storage = $this->getMockBuilder($storageClass)
			->disableOriginalConstructor()
			->onlyMethods(['getId', 'isLocal'])
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
