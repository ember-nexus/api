<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Type\S3;

use App\Contract\NodeElementInterface;
use App\Contract\UploadInterface;
use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Type\S3\MergeFileChunksOperationFactory;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\FileService;
use App\Type\S3\MergeFileChunksOperation;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(MergeFileChunksOperationFactory::class)]
class MergeFileChunksOperationFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function buildMergeFileChunksOperationFactory(
        ?EmberNexusConfiguration $emberNexusConfiguration = null,
        ?ElementManager $elementManager = null,
        ?ElementService $elementService = null,
        ?FileService $fileService = null,
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
    ): MergeFileChunksOperationFactory {
        return new MergeFileChunksOperationFactory(
            $emberNexusConfiguration ?? $this->prophesize(EmberNexusConfiguration::class)->reveal(),
            $elementManager ?? $this->prophesize(ElementManager::class)->reveal(),
            $elementService ?? $this->prophesize(ElementService::class)->reveal(),
            $fileService ?? $this->prophesize(FileService::class)->reveal(),
            $client400BadContentExceptionFactory ?? $this->prophesize(Client400BadContentExceptionFactory::class)->reveal(),
        );
    }

    public function testCreateMergeFileOperationFromUpload(): void
    {
        $uploadId = Uuid::fromString('8fb0017a-c2af-4140-96fd-c8e7bbe03f5e');
        $uploadTarget = Uuid::fromString('25825901-d004-4c13-a9d9-a0f813de49b4');

        $upload = $this->prophesize(UploadInterface::class);
        $upload->isUploadComplete()->shouldBeCalledOnce()->willReturn(true);
        $upload->getAlreadyUploadedChunks()->shouldBeCalledOnce()->willReturn(3);
        $upload->getId()->shouldBeCalledOnce()->willReturn($uploadId);
        $upload->getUploadTarget()->shouldBeCalledTimes(2)->willReturn($uploadTarget);
        $upload->getExtension()->shouldBeCalledOnce()->willReturn('ext');

        $element = $this->prophesize(NodeElementInterface::class);
        $element->hasProperty(Argument::is('file'))->shouldBeCalledOnce()->willReturn(false);
        $element = $element->reveal();

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3UploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $emberNexusConfiguration->getFileS3StorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($uploadTarget))->shouldBeCalledOnce()->willReturn($element);

        $fileService = $this->prophesize(FileService::class);
        $fileService->getUploadBucketKey(Argument::is($uploadId), Argument::is(1))->shouldBeCalledOnce()->willReturn('upload-key-0001');
        $fileService->getUploadBucketKey(Argument::is($uploadId), Argument::is(2))->shouldBeCalledOnce()->willReturn('upload-key-0002');
        $fileService->getUploadBucketKey(Argument::is($uploadId), Argument::is(3))->shouldBeCalledOnce()->willReturn('upload-key-0003');
        $fileService->getStorageBucketKey(Argument::is($uploadTarget), Argument::is('ext'))->shouldBeCalledOnce()->willReturn('target-key.ext');

        $mergeFileChunksOperationFactory = $this->buildMergeFileChunksOperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            elementManager: $elementManager->reveal(),
            fileService: $fileService->reveal()
        );

        $mergeFileChunksOperation = $mergeFileChunksOperationFactory->createMergeFileOperationFromUpload($upload->reveal());

        $this->assertInstanceOf(MergeFileChunksOperation::class, $mergeFileChunksOperation);
        $this->assertSame('upload-bucket', $mergeFileChunksOperation->getUploadBucket());
        $this->assertSame(['upload-key-0001', 'upload-key-0002', 'upload-key-0003'], $mergeFileChunksOperation->getUploadKeys());
        $this->assertSame('storage-bucket', $mergeFileChunksOperation->getStorageBucket());
        $this->assertNull($mergeFileChunksOperation->getPreviousStorageKey());
        $this->assertSame('target-key.ext', $mergeFileChunksOperation->getStorageKey());
    }

    public function testCreateMergeFileOperationFromUploadSetsPreviousStorageKeyWhenPossible(): void
    {
        $uploadId = Uuid::fromString('8fb0017a-c2af-4140-96fd-c8e7bbe03f5e');
        $uploadTarget = Uuid::fromString('25825901-d004-4c13-a9d9-a0f813de49b4');

        $upload = $this->prophesize(UploadInterface::class);
        $upload->isUploadComplete()->shouldBeCalledOnce()->willReturn(true);
        $upload->getAlreadyUploadedChunks()->shouldBeCalledOnce()->willReturn(2);
        $upload->getId()->shouldBeCalledOnce()->willReturn($uploadId);
        $upload->getUploadTarget()->shouldBeCalledTimes(3)->willReturn($uploadTarget);
        $upload->getExtension()->shouldBeCalledOnce()->willReturn('ext');

        $element = $this->prophesize(NodeElementInterface::class);
        $element->hasProperty(Argument::is('file'))->shouldBeCalledOnce()->willReturn(true);
        $element = $element->reveal();

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3UploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $emberNexusConfiguration->getFileS3StorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($uploadTarget))->shouldBeCalledOnce()->willReturn($element);

        $fileService = $this->prophesize(FileService::class);
        $fileService->getUploadBucketKey(Argument::is($uploadId), Argument::is(1))->shouldBeCalledOnce()->willReturn('upload-key-0001');
        $fileService->getUploadBucketKey(Argument::is($uploadId), Argument::is(2))->shouldBeCalledOnce()->willReturn('upload-key-0002');
        $fileService->getStorageBucketKey(Argument::is($uploadTarget), Argument::is('ext'))->shouldBeCalledOnce()->willReturn('target-key.ext');
        $fileService->getStorageBucketKey(Argument::is($uploadTarget), Argument::is('prev'))->shouldBeCalledOnce()->willReturn('target-key.prev');

        $elementService = $this->prophesize(ElementService::class);
        $elementService->getFileNameExtension(Argument::is($element))->shouldBeCalledOnce()->willReturn('prev');

        $mergeFileChunksOperationFactory = $this->buildMergeFileChunksOperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            elementManager: $elementManager->reveal(),
            elementService: $elementService->reveal(),
            fileService: $fileService->reveal()
        );

        $mergeFileChunksOperation = $mergeFileChunksOperationFactory->createMergeFileOperationFromUpload($upload->reveal());

        $this->assertInstanceOf(MergeFileChunksOperation::class, $mergeFileChunksOperation);
        $this->assertSame('upload-bucket', $mergeFileChunksOperation->getUploadBucket());
        $this->assertSame(['upload-key-0001', 'upload-key-0002'], $mergeFileChunksOperation->getUploadKeys());
        $this->assertSame('storage-bucket', $mergeFileChunksOperation->getStorageBucket());
        $this->assertSame('target-key.prev', $mergeFileChunksOperation->getPreviousStorageKey());
        $this->assertSame('target-key.ext', $mergeFileChunksOperation->getStorageKey());
    }

    public function testCreateMergeFileOperationFromUploadThrowsWhenUploadIsIncomplete(): void
    {
        $upload = $this->prophesize(UploadInterface::class);
        $upload->isUploadComplete()->shouldBeCalledOnce()->willReturn(false);

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("'MergeFileChunksOperation' requires 'Upload' to be complete."))->shouldBeCalledOnce()->willReturn($exception);

        $mergeFileChunksOperationFactory = $this->buildMergeFileChunksOperationFactory(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $mergeFileChunksOperationFactory->createMergeFileOperationFromUpload($upload->reveal());
    }
}
