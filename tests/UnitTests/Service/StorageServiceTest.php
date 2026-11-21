<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\StorageService;
use App\Type\Uuid;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
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
            $storageService->getUploadBucketKey(Uuid::fromString('02IiNr0G8viOVKkRCAnGVctblMH1uQTu3OQ0v'), -1);
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
            $storageService->getUploadBucketKey(Uuid::fromString('02IiNr0G8viOVKkRCAnGVctblMH1uQTu3OQ0v'), 10000);
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

        $key = $storageService->getUploadBucketKey(Uuid::fromString('02IiNr0G8viOVKkRCAnGVctblMH1uQTu3OQ0v'), 1234);
        $this->assertSame('cf/89/83/02IiNr0G8viOVKkRCAnGVctblMH1uQTu3OQ0v-1234.wip', $key);
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

        $key = $storageService->getUploadBucketKey(Uuid::fromString('02IiNr0G8viOVKkRCAnGVctblMH1uQTu3OQ0v'), 12, '3mJr7AoUXx2Wqd9G');
        $this->assertSame('cf/89/83/02IiNr0G8viOVKkRCAnGVctblMH1uQTu3OQ0v-0012-3mJr7AoUXx2Wqd9G.wip', $key);
    }

    public function testGetUploadBucketKeyRejectsInvalidChunkId(): void
    {
        $storageService = $this->buildStorageService();

        $this->expectException(Server500LogicErrorException::class);
        $storageService->getUploadBucketKey(Uuid::fromString('02IiNr0G8viOVKkRCAnGVctblMH1uQTu3OQ0v'), 1, '../etc/passwd');
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
        $storageService->getUploadBucketKey(Uuid::fromString('02IiNr0G8viOVKkRCAnGVctblMH1uQTu3OQ0v'), 1, $chunkId);
    }

    public function testGetStorageBucketKeySupportsOtherExtension(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3StorageBucketLevels()->shouldBeCalledOnce()->willReturn(3);
        $emberNexusConfiguration->getFileS3StorageBucketLevelLength()->shouldBeCalledOnce()->willReturn(2);

        $storageService = $this->buildStorageService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal()
        );

        $key = $storageService->getStorageBucketKey(Uuid::fromString('02IiNr0G8viOVKk4pL8jtJPNyKx05n9qpJb8Q'), 'png');
        $this->assertSame('8b/d9/dc/02IiNr0G8viOVKk4pL8jtJPNyKx05n9qpJb8Q.png', $key);
    }

    public function testGetStorageBucketKeyWithEmptyExtensionHasNoTrailingDot(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3StorageBucketLevels()->shouldBeCalledOnce()->willReturn(3);
        $emberNexusConfiguration->getFileS3StorageBucketLevelLength()->shouldBeCalledOnce()->willReturn(2);

        $storageService = $this->buildStorageService(
            emberNexusConfiguration: $emberNexusConfiguration->reveal()
        );

        $key = $storageService->getStorageBucketKey(Uuid::fromString('02IiNr0G8viOVKk4pL8jtJPNyKx05n9qpJb8Q'), '');
        $this->assertSame('8b/d9/dc/02IiNr0G8viOVKk4pL8jtJPNyKx05n9qpJb8Q', $key);
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

        $uuid = Uuid::fromString('02IiNr0G8viOVKjjYIsWvZXUJEs5I5bXOooTW');

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
            $this->assertSame('Unable to generate nested folder structure as long as product of levels and level length exceeds length of the random part of the uuid.', $e->getDetail());
        }

        $generatedStructure = $storageService->uuidToNestedFolderStructure($uuid);
        $this->assertSame('02IiNr0G8viOVKjjYIsWvZXUJEs5I5bXOooTW', $generatedStructure);

        $generatedStructure = $storageService->uuidToNestedFolderStructure($uuid, 1, 1);
        $this->assertSame('2/02IiNr0G8viOVKjjYIsWvZXUJEs5I5bXOooTW', $generatedStructure);

        $generatedStructure = $storageService->uuidToNestedFolderStructure($uuid, 1, 3);
        $this->assertSame('297/02IiNr0G8viOVKjjYIsWvZXUJEs5I5bXOooTW', $generatedStructure);

        $generatedStructure = $storageService->uuidToNestedFolderStructure($uuid, 3, 1);
        $this->assertSame('2/9/7/02IiNr0G8viOVKjjYIsWvZXUJEs5I5bXOooTW', $generatedStructure);

        $generatedStructure = $storageService->uuidToNestedFolderStructure($uuid, 2, 2);
        $this->assertSame('29/71/02IiNr0G8viOVKjjYIsWvZXUJEs5I5bXOooTW', $generatedStructure);

        $generatedStructure = $storageService->uuidToNestedFolderStructure($uuid, 6, 4);
        $this->assertSame('2971/99c2/d6ca/ac3e/e843/f8c2/02IiNr0G8viOVKjjYIsWvZXUJEs5I5bXOooTW', $generatedStructure);

        $generatedStructure = $storageService->uuidToNestedFolderStructure($uuid, 12, 2);
        $this->assertSame('29/71/99/c2/d6/ca/ac/3e/e8/43/f8/c2/02IiNr0G8viOVKjjYIsWvZXUJEs5I5bXOooTW', $generatedStructure);
    }
}
