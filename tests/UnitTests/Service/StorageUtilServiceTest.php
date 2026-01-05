<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Service;

use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Service\StorageUtilService;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(StorageUtilService::class)]
#[AllowMockObjectsWithoutExpectations]
class StorageUtilServiceTest extends TestCase
{
    use ProphecyTrait;

    private function getStorageUtilService(
        ?EmberNexusConfiguration $emberNexusConfiguration = null,
    ): StorageUtilService {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $server500Bag = $this->createMock(ParameterBagInterface::class);
        $server500Bag->method('get')->willReturn('dev');
        $server500LogicExceptionFactory = new Server500LogicExceptionFactory(
            $urlGenerator,
            $server500Bag,
            $this->createMock(LoggerInterface::class)
        );

        return new StorageUtilService(
            $emberNexusConfiguration ?? $this->createMock(EmberNexusConfiguration::class),
            $server500LogicExceptionFactory,
        );
    }

    public function testGetUploadBucketKeyThrowsOnNegativeIndex(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileUploadChunkDigitsLength()->shouldBeCalledOnce()->willReturn(4);

        $storageUtilService = $this->getStorageUtilService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal()
        );

        try {
            $storageUtilService->getUploadBucketKey(Uuid::fromString('8ba117cf-8983-4f13-be93-415e175cb64d'), -1);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var Server500LogicErrorException $e
             */
            $this->assertSame('Chunk index can not be less than 0.', $e->getDetail());
        }
    }

    public function testGetUploadBucketKeyThrowsWhenIndexExceedsMaxDigitsValue(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileUploadChunkDigitsLength()->shouldBeCalledOnce()->willReturn(4);

        $storageUtilService = $this->getStorageUtilService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal()
        );

        try {
            $storageUtilService->getUploadBucketKey(Uuid::fromString('8ba117cf-8983-4f13-be93-415e175cb64d'), 10000);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var Server500LogicErrorException $e
             */
            $this->assertSame('Chunk index can not be longer than 4 digits, i.e. bigger than 9999.', $e->getDetail());
        }
    }

    public function testGetUploadBucketKeyUsesBinAsDefaultExtension(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileUploadChunkDigitsLength()->shouldBeCalledOnce()->willReturn(4);
        $emberNexusConfiguration->getFileS3UploadBucketLevels()->shouldBeCalledOnce()->willReturn(3);
        $emberNexusConfiguration->getFileS3UploadBucketLevelLength()->shouldBeCalledOnce()->willReturn(2);

        $storageUtilService = $this->getStorageUtilService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal()
        );

        $key = $storageUtilService->getUploadBucketKey(Uuid::fromString('8ba117cf-8983-4f13-be93-415e175cb64d'), 1234);
        $this->assertSame('8b/a1/17/8ba117cf-8983-4f13-be93-415e175cb64d-1234.bin', $key);
    }

    public function testGetUploadBucketKeySupportsOtherExtension(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileUploadChunkDigitsLength()->shouldBeCalledOnce()->willReturn(4);
        $emberNexusConfiguration->getFileS3UploadBucketLevels()->shouldBeCalledOnce()->willReturn(3);
        $emberNexusConfiguration->getFileS3UploadBucketLevelLength()->shouldBeCalledOnce()->willReturn(2);

        $storageUtilService = $this->getStorageUtilService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal()
        );

        $key = $storageUtilService->getUploadBucketKey(Uuid::fromString('befb450d-ae5e-4ed7-9f24-134f8a6a140a'), 52, 'jpg');
        $this->assertSame('be/fb/45/befb450d-ae5e-4ed7-9f24-134f8a6a140a-0052.jpg', $key);
    }

    public function testGetStorageBucketKeyUsesBinAsDefaultExtension(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3StorageBucketLevels()->shouldBeCalledOnce()->willReturn(3);
        $emberNexusConfiguration->getFileS3StorageBucketLevelLength()->shouldBeCalledOnce()->willReturn(2);

        $storageUtilService = $this->getStorageUtilService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal()
        );

        $key = $storageUtilService->getStorageBucketKey(Uuid::fromString('3eda4085-968b-46a8-a729-cdaaa30b1d3f'));
        $this->assertSame('3e/da/40/3eda4085-968b-46a8-a729-cdaaa30b1d3f.bin', $key);
    }

    public function testGetStorageBucketKeySupportsOtherExtension(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3StorageBucketLevels()->shouldBeCalledOnce()->willReturn(3);
        $emberNexusConfiguration->getFileS3StorageBucketLevelLength()->shouldBeCalledOnce()->willReturn(2);

        $storageUtilService = $this->getStorageUtilService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal()
        );

        $key = $storageUtilService->getStorageBucketKey(Uuid::fromString('5dbd948b-d9dc-4b6c-af1c-c12dc8120755'), 'png');
        $this->assertSame('5d/bd/94/5dbd948b-d9dc-4b6c-af1c-c12dc8120755.png', $key);
    }

    public function testUuidToNestedFolderStructure(): void
    {
        $storageUtilService = $this->getStorageUtilService();

        $uuid = Uuid::fromString('3207d629-7199-4c2d-9b2a-b0fba10fe309');

        try {
            $storageUtilService->uuidToNestedFolderStructure($uuid, -1);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var Server500LogicErrorException $e
             */
            $this->assertSame('Unable to generate nested folder structure from uuid with negative level argument.', $e->getDetail());
        }

        try {
            $storageUtilService->uuidToNestedFolderStructure($uuid, 0, -1);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var Server500LogicErrorException $e
             */
            $this->assertSame('Unable to generate nested folder structure from uuid with level length less than 1.', $e->getDetail());
        }

        try {
            $storageUtilService->uuidToNestedFolderStructure($uuid, 0, 0);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var Server500LogicErrorException $e
             */
            $this->assertSame('Unable to generate nested folder structure from uuid with level length less than 1.', $e->getDetail());
        }

        try {
            $storageUtilService->uuidToNestedFolderStructure($uuid, 8, 9);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var Server500LogicErrorException $e
             */
            $this->assertSame('Unable to generate nested folder structure as long as product of levels and level length exceeds length of uuid without dashes.', $e->getDetail());
        }

        $generatedStructure = $storageUtilService->uuidToNestedFolderStructure($uuid);
        $this->assertSame('3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageUtilService->uuidToNestedFolderStructure($uuid, 1, 1);
        $this->assertSame('3/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageUtilService->uuidToNestedFolderStructure($uuid, 1, 3);
        $this->assertSame('320/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageUtilService->uuidToNestedFolderStructure($uuid, 3, 1);
        $this->assertSame('3/2/0/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageUtilService->uuidToNestedFolderStructure($uuid, 2, 2);
        $this->assertSame('32/07/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageUtilService->uuidToNestedFolderStructure($uuid, 6, 5);
        $this->assertSame('3207d/62971/994c2/d9b2a/b0fba/10fe3/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageUtilService->uuidToNestedFolderStructure($uuid, 15, 2);
        $this->assertSame('32/07/d6/29/71/99/4c/2d/9b/2a/b0/fb/a1/0f/e3/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);
    }
}
