<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\StorageService;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(StorageService::class)]
#[AllowMockObjectsWithoutExpectations]
class StorageServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildStorageService(
        ?EmberNexusConfiguration $emberNexusConfiguration = null,
    ): StorageService {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $server500Bag = $this->createMock(ParameterBagInterface::class);
        $server500Bag->method('get')->willReturn('dev');
        $server500LogicExceptionFactory = new Server500LogicErrorExceptionFactory(
            $urlGenerator,
            $server500Bag,
            $this->createMock(LoggerInterface::class)
        );

        return new StorageService(
            $emberNexusConfiguration ?? $this->createMock(EmberNexusConfiguration::class),
            $server500LogicExceptionFactory,
        );
    }

    public function testGetUploadBucketKeyThrowsOnNegativeIndex(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileUploadChunkDigitsLength()->shouldBeCalledOnce()->willReturn(4);

        $storageService = $this->buildStorageService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal()
        );

        try {
            $storageService->getUploadBucketKey(Uuid::fromString('8ba117cf-8983-4f13-be93-415e175cb64d'), -1);
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

        $storageService = $this->buildStorageService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal()
        );

        try {
            $storageService->getUploadBucketKey(Uuid::fromString('8ba117cf-8983-4f13-be93-415e175cb64d'), 10000);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var Server500LogicErrorException $e
             */
            $this->assertSame('Chunk index can not be longer than 4 digits, i.e. bigger than 9999.', $e->getDetail());
        }
    }

    public function testGetUploadBucketKey(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileUploadChunkDigitsLength()->shouldBeCalledOnce()->willReturn(4);
        $emberNexusConfiguration->getFileS3UploadBucketLevels()->shouldBeCalledOnce()->willReturn(3);
        $emberNexusConfiguration->getFileS3UploadBucketLevelLength()->shouldBeCalledOnce()->willReturn(2);

        $storageService = $this->buildStorageService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal()
        );

        $key = $storageService->getUploadBucketKey(Uuid::fromString('8ba117cf-8983-4f13-be93-415e175cb64d'), 1234);
        $this->assertSame('8b/a1/17/8ba117cf-8983-4f13-be93-415e175cb64d-1234.wip', $key);
    }

    public function testGetUploadBucketKeyWithChunkId(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileUploadChunkDigitsLength()->willReturn(4);
        $emberNexusConfiguration->getFileS3UploadBucketLevels()->willReturn(3);
        $emberNexusConfiguration->getFileS3UploadBucketLevelLength()->willReturn(2);

        $storageService = $this->buildStorageService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal()
        );

        $key = $storageService->getUploadBucketKey(Uuid::fromString('8ba117cf-8983-4f13-be93-415e175cb64d'), 12, '3mJr7AoUXx2Wqd9G');
        $this->assertSame('8b/a1/17/8ba117cf-8983-4f13-be93-415e175cb64d-0012-3mJr7AoUXx2Wqd9G.wip', $key);
    }

    public function testGetUploadBucketKeyRejectsInvalidChunkId(): void
    {
        $storageService = $this->buildStorageService();

        $this->expectException(Server500LogicErrorException::class);
        $storageService->getUploadBucketKey(Uuid::fromString('8ba117cf-8983-4f13-be93-415e175cb64d'), 1, '../etc/passwd');
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidChunkIdProvider(): array
    {
        return [
            'dash' => ['abc-def'],
            'dot' => ['abc.def'],
            'plus' => ['abc+def'],
            'slash' => ['ab/cd'],
            'empty' => [''],
            'too long' => [str_repeat('a', 65)],
        ];
    }

    #[DataProvider('invalidChunkIdProvider')]
    public function testGetUploadBucketKeyRejectsInvalidChunkIdCharactersAndLengths(string $chunkId): void
    {
        $storageService = $this->buildStorageService();

        $this->expectException(Server500LogicErrorException::class);
        $storageService->getUploadBucketKey(Uuid::fromString('8ba117cf-8983-4f13-be93-415e175cb64d'), 1, $chunkId);
    }

    public function testGetStorageBucketKeySupportsOtherExtension(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3StorageBucketLevels()->shouldBeCalledOnce()->willReturn(3);
        $emberNexusConfiguration->getFileS3StorageBucketLevelLength()->shouldBeCalledOnce()->willReturn(2);

        $storageService = $this->buildStorageService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal()
        );

        $key = $storageService->getStorageBucketKey(Uuid::fromString('5dbd948b-d9dc-4b6c-af1c-c12dc8120755'), 'png');
        $this->assertSame('5d/bd/94/5dbd948b-d9dc-4b6c-af1c-c12dc8120755.png', $key);
    }

    public function testGetStorageBucketKeyWithEmptyExtensionHasNoTrailingDot(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3StorageBucketLevels()->shouldBeCalledOnce()->willReturn(3);
        $emberNexusConfiguration->getFileS3StorageBucketLevelLength()->shouldBeCalledOnce()->willReturn(2);

        $storageService = $this->buildStorageService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal()
        );

        $key = $storageService->getStorageBucketKey(Uuid::fromString('5dbd948b-d9dc-4b6c-af1c-c12dc8120755'), '');
        $this->assertSame('5d/bd/94/5dbd948b-d9dc-4b6c-af1c-c12dc8120755', $key);
    }

    public function testAppendExtension(): void
    {
        $storageService = $this->buildStorageService();
        $this->assertSame('path/file.txt', $storageService->appendExtension('path/file', 'txt'));
        $this->assertSame('path/file', $storageService->appendExtension('path/file', ''));
    }

    public function testUuidToNestedFolderStructure(): void
    {
        $storageService = $this->buildStorageService();

        $uuid = Uuid::fromString('3207d629-7199-4c2d-9b2a-b0fba10fe309');

        try {
            $storageService->uuidToNestedFolderStructure($uuid, -1);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var Server500LogicErrorException $e
             */
            $this->assertSame('Unable to generate nested folder structure from uuid with negative level argument.', $e->getDetail());
        }

        try {
            $storageService->uuidToNestedFolderStructure($uuid, 0, -1);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var Server500LogicErrorException $e
             */
            $this->assertSame('Unable to generate nested folder structure from uuid with level length less than 1.', $e->getDetail());
        }

        try {
            $storageService->uuidToNestedFolderStructure($uuid, 0, 0);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var Server500LogicErrorException $e
             */
            $this->assertSame('Unable to generate nested folder structure from uuid with level length less than 1.', $e->getDetail());
        }

        try {
            $storageService->uuidToNestedFolderStructure($uuid, 8, 9);
        } catch (Exception $e) {
            $this->assertInstanceOf(Server500LogicErrorException::class, $e);
            /**
             * @var Server500LogicErrorException $e
             */
            $this->assertSame('Unable to generate nested folder structure as long as product of levels and level length exceeds length of uuid without dashes.', $e->getDetail());
        }

        $generatedStructure = $storageService->uuidToNestedFolderStructure($uuid);
        $this->assertSame('3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageService->uuidToNestedFolderStructure($uuid, 1, 1);
        $this->assertSame('3/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageService->uuidToNestedFolderStructure($uuid, 1, 3);
        $this->assertSame('320/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageService->uuidToNestedFolderStructure($uuid, 3, 1);
        $this->assertSame('3/2/0/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageService->uuidToNestedFolderStructure($uuid, 2, 2);
        $this->assertSame('32/07/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageService->uuidToNestedFolderStructure($uuid, 6, 5);
        $this->assertSame('3207d/62971/994c2/d9b2a/b0fba/10fe3/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);

        $generatedStructure = $storageService->uuidToNestedFolderStructure($uuid, 15, 2);
        $this->assertSame('32/07/d6/29/71/99/4c/2d/9b/2a/b0/fb/a1/0f/e3/3207d629-7199-4c2d-9b2a-b0fba10fe309', $generatedStructure);
    }
}
