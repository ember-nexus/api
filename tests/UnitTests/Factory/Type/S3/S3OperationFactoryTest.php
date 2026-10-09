<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Type\S3;

use App\Contract\NodeElementInterface;
use App\Contract\Request\PartialUploadRequestInterface;
use App\Contract\Request\ResumableUploadRequestInterface;
use App\Contract\S3\UploadFileChunkOperationInterface;
use App\Contract\S3\UploadFileOperationInterface;
use App\Contract\UploadInterface;
use App\Exception\Client400BadContentException;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\S3OperationFactory;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\StorageService;
use App\Service\MimeTypeService;
use App\Type\NodeElement;
use App\Type\S3\FileOperation;
use App\Type\S3\MergeFileChunksOperation;
use App\Type\S3\UploadFileChunkOperation;
use App\Type\S3\UploadFileOperation;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(S3OperationFactory::class)]
class S3OperationFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function buildS3OperationFactory(
        ?EmberNexusConfiguration $emberNexusConfiguration = null,
        ?ElementManager $elementManager = null,
        ?ElementService $elementService = null,
        ?StorageService $storageService = null,
        ?MimeTypeService $mimeTypeService = null,
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
        ?Server500LogicErrorExceptionFactory $server500LogicExceptionFactory = null,
    ): S3OperationFactory {
        return new S3OperationFactory(
            $emberNexusConfiguration ?? $this->prophesize(EmberNexusConfiguration::class)->reveal(),
            $elementManager ?? $this->prophesize(ElementManager::class)->reveal(),
            $elementService ?? $this->prophesize(ElementService::class)->reveal(),
            $mimeTypeService ?? $this->prophesize(MimeTypeService::class)->reveal(),
            $storageService ?? $this->prophesize(StorageService::class)->reveal(),
            $client400BadContentExceptionFactory ?? $this->prophesize(Client400BadContentExceptionFactory::class)->reveal(),
            $server500LogicExceptionFactory ?? $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        );
    }

    public function testCreateFileOperationFromUpload(): void
    {
        $id = Uuid::fromString('2e92baf4-850e-4a98-ab03-a5b2f717808f');

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3UploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');

        $storageService = $this->prophesize(StorageService::class);
        $storageService->getUploadBucketKey(Argument::is($id), Argument::is(3), Argument::is('0123456789abcdef'))->shouldBeCalledOnce()->willReturn('upload-key');

        $factory = $this->buildS3OperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            storageService: $storageService->reveal()
        );

        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->shouldBeCalledOnce()->willReturn($id);

        $operation = $factory->createFileOperationFromUpload($upload->reveal(), 3, '0123456789abcdef');

        $this->assertInstanceOf(FileOperation::class, $operation);
        $this->assertSame('upload-bucket', $operation->getBucket());
        $this->assertSame('upload-key', $operation->getKey());
    }

    public function testCreateFileOperationFromElement(): void
    {
        $id = Uuid::fromString('2e92baf4-850e-4a98-ab03-a5b2f717808f');

        $element = $this->prophesize(NodeElement::class);
        $element->getId()->shouldBeCalledOnce()->willReturn($id);
        $element = $element->reveal();

        $elementService = $this->prophesize(ElementService::class);
        $elementService->getFileNameExtension(Argument::is($element))->shouldBeCalledOnce()->willReturn('test');

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3StorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');

        $storageService = $this->prophesize(StorageService::class);
        $storageService->getStorageBucketKey(Argument::is($id), Argument::is('test'))->shouldBeCalledOnce()->willReturn('storage-key.test');

        $factory = $this->buildS3OperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            storageService: $storageService->reveal(),
            elementService: $elementService->reveal()
        );

        $operation = $factory->createFileOperationFromElement($element);

        $this->assertInstanceOf(FileOperation::class, $operation);
        $this->assertSame('storage-bucket', $operation->getBucket());
        $this->assertSame('storage-key.test', $operation->getKey());
    }

    public function testCreateFileOperationFromElementFailsOnMissingElementId(): void
    {
        $element = $this->prophesize(NodeElementInterface::class)->reveal();
        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();

        $server500LogicExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicExceptionFactory->createFromTemplate(Argument::is('Expected elementId to be not null.'))->shouldBeCalledOnce()->willReturn($exception);

        $factory = $this->buildS3OperationFactory(
            server500LogicExceptionFactory: $server500LogicExceptionFactory->reveal()
        );

        $this->expectException(Server500LogicErrorException::class);

        $factory->createFileOperationFromElement($element);
    }

    public function testCreateUploadFileOperationFromResumableUploadRequest(): void
    {
        $elementId = Uuid::fromString('9c981309-8269-4469-a595-282eb62ec04f');

        $resumableUploadRequest = $this->prophesize(ResumableUploadRequestInterface::class);
        $resumableUploadRequest->isUploadComplete()->shouldBeCalledOnce()->willReturn(true);
        $resumableUploadRequest->getElementId()->shouldBeCalledOnce()->willReturn($elementId);
        $resumableUploadRequest->getContent()->shouldBeCalledOnce()->willReturn('some content');
        $resumableUploadRequest->getExtension()->shouldBeCalledOnce()->willReturn('txt');
        $resumableUploadRequest->getContentLength()->shouldBeCalledOnce()->willReturn(12);

        $element = $this->prophesize(NodeElementInterface::class);
        $element = $element->reveal();

        $elementService = $this->prophesize(ElementService::class);
        $elementService->getStorageKeyOfFile(Argument::is($element))->shouldBeCalledOnce()->willReturn(null);

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($elementId))->shouldBeCalledOnce()->willReturn($element);

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3UploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $emberNexusConfiguration->getFileS3StorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');

        $storageService = $this->prophesize(StorageService::class);
        $storageService->getUploadBucketKey(Argument::is($elementId), Argument::is(0))->shouldBeCalledOnce()->willReturn('upload-key');
        $storageService->getStorageBucketKey(Argument::is($elementId), Argument::is('txt'))->shouldBeCalledOnce()->willReturn('storage-key.txt');

        $mimeTypeService = $this->prophesize(MimeTypeService::class);
        $mimeTypeService->getMimeTypeFromResource(Argument::is('some content'))->shouldBeCalledOnce()->willReturn('text/plain');

        $factory = $this->buildS3OperationFactory(
            elementService: $elementService->reveal(),
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            elementManager: $elementManager->reveal(),
            storageService: $storageService->reveal(),
            mimeTypeService: $mimeTypeService->reveal()
        );

        $uploadFileOperation = $factory->createUploadFileOperationFromResumableUploadRequest($resumableUploadRequest->reveal());

        $this->assertInstanceOf(UploadFileOperation::class, $uploadFileOperation);
        $this->assertSame('upload-bucket', $uploadFileOperation->getUploadBucket());
        $this->assertSame('upload-key', $uploadFileOperation->getUploadKey());
        $this->assertSame('storage-bucket', $uploadFileOperation->getStorageBucket());
        $this->assertNull($uploadFileOperation->getPreviousStorageKey());
        $this->assertSame('storage-key.txt', $uploadFileOperation->getStorageKey());
        $this->assertSame('some content', $uploadFileOperation->getContent());
        $this->assertSame(12, $uploadFileOperation->getContentLength());
        $this->assertSame('text/plain', $uploadFileOperation->getMimeType());
    }

    public function testCreateUploadFileOperationFromResumableUploadRequestSetsPreviousStorageKeyWhenPossible(): void
    {
        $elementId = Uuid::fromString('9c981309-8269-4469-a595-282eb62ec04f');

        $resumableUploadRequest = $this->prophesize(ResumableUploadRequestInterface::class);
        $resumableUploadRequest->isUploadComplete()->shouldBeCalledOnce()->willReturn(true);
        $resumableUploadRequest->getElementId()->shouldBeCalledOnce()->willReturn($elementId);
        $resumableUploadRequest->getContent()->shouldBeCalledOnce()->willReturn('some content');
        $resumableUploadRequest->getExtension()->shouldBeCalledOnce()->willReturn('txt');
        $resumableUploadRequest->getContentLength()->shouldBeCalledOnce()->willReturn(12);

        $element = $this->prophesize(NodeElementInterface::class);
        $element = $element->reveal();

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($elementId))->shouldBeCalledOnce()->willReturn($element);

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3UploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $emberNexusConfiguration->getFileS3StorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');

        $storageService = $this->prophesize(StorageService::class);
        $storageService->getUploadBucketKey(Argument::is($elementId), Argument::is(0))->shouldBeCalledOnce()->willReturn('upload-key');
        $storageService->getStorageBucketKey(Argument::is($elementId), Argument::is('txt'))->shouldBeCalledOnce()->willReturn('storage-key.txt');

        $mimeTypeService = $this->prophesize(MimeTypeService::class);
        $mimeTypeService->getMimeTypeFromResource(Argument::is('some content'))->shouldBeCalledOnce()->willReturn('text/plain');

        $elementService = $this->prophesize(ElementService::class);
        $elementService->getStorageKeyOfFile(Argument::is($element))->shouldBeCalledOnce()->willReturn('storage-key.prev');

        $factory = $this->buildS3OperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            elementManager: $elementManager->reveal(),
            elementService: $elementService->reveal(),
            storageService: $storageService->reveal(),
            mimeTypeService: $mimeTypeService->reveal()
        );

        $uploadFileOperation = $factory->createUploadFileOperationFromResumableUploadRequest($resumableUploadRequest->reveal());

        $this->assertInstanceOf(UploadFileOperation::class, $uploadFileOperation);
        $this->assertSame('storage-key.prev', $uploadFileOperation->getPreviousStorageKey());
    }

    public function testCreateUploadFileOperationFromResumableUploadRequestFailsWhenUploadIsIncomplete(): void
    {
        $resumableUploadRequest = $this->prophesize(ResumableUploadRequestInterface::class);
        $resumableUploadRequest->isUploadComplete()->shouldBeCalledOnce()->willReturn(false);

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("'UploadFileOperation' requires 'ResumableUploadRequest' to contain the whole content, i.e. be a non-chunked upload request."))->shouldBeCalledOnce()->willReturn($exception);

        $factory = $this->buildS3OperationFactory(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $factory->createUploadFileOperationFromResumableUploadRequest($resumableUploadRequest->reveal());
    }

    public function testCreateUploadFileOperationFromElementAndResource(): void
    {
        $elementId = Uuid::fromString('9c981309-8269-4469-a595-282eb62ec04f');

        $element = $this->prophesize(NodeElementInterface::class);
        $element->getId()->shouldBeCalledOnce()->willReturn($elementId);
        $element = $element->reveal();

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3UploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $emberNexusConfiguration->getFileS3StorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');

        $storageService = $this->prophesize(StorageService::class);
        $storageService->getUploadBucketKey(Argument::is($elementId), Argument::is(0))->shouldBeCalledOnce()->willReturn('upload-key');
        $storageService->getStorageBucketKey(Argument::is($elementId), Argument::is('ext'))->shouldBeCalledOnce()->willReturn('storage-key.ext');

        $mimeTypeService = $this->prophesize(MimeTypeService::class);
        $mimeTypeService->getMimeTypeFromResource(Argument::is('some content'))->shouldBeCalledOnce()->willReturn('text/plain');

        $elementService = $this->prophesize(ElementService::class);
        $elementService->getFileNameExtension(Argument::is($element))->shouldBeCalledOnce()->willReturn('ext');

        $factory = $this->buildS3OperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            elementService: $elementService->reveal(),
            storageService: $storageService->reveal(),
            mimeTypeService: $mimeTypeService->reveal()
        );

        $uploadFileOperation = $factory->createUploadFileOperationFromElementAndResource($element, 'some content');

        $this->assertInstanceOf(UploadFileOperation::class, $uploadFileOperation);
        $this->assertSame('upload-bucket', $uploadFileOperation->getUploadBucket());
        $this->assertSame('upload-key', $uploadFileOperation->getUploadKey());
        $this->assertSame('storage-bucket', $uploadFileOperation->getStorageBucket());
        $this->assertNull($uploadFileOperation->getPreviousStorageKey());
        $this->assertSame('storage-key.ext', $uploadFileOperation->getStorageKey());
        $this->assertSame('some content', $uploadFileOperation->getContent());
        $this->assertNull($uploadFileOperation->getContentLength());
        $this->assertSame('text/plain', $uploadFileOperation->getMimeType());
    }

    public function testCreateUploadFileOperationFromElementAndResourceFailsWhenElementIdIsMissing(): void
    {
        $element = $this->prophesize(NodeElementInterface::class);
        $element->getId()->shouldBeCalledOnce()->willReturn(null);
        $element = $element->reveal();

        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();

        $server500LogicExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicExceptionFactory->createFromTemplate(Argument::is('Expected element.id to not be null.'))->shouldBeCalledOnce()->willReturn($exception);

        $factory = $this->buildS3OperationFactory(
            server500LogicExceptionFactory: $server500LogicExceptionFactory->reveal()
        );

        $this->expectException(Server500LogicErrorException::class);

        $factory->createUploadFileOperationFromElementAndResource($element, 'some content');
    }

    public function testCreateUploadFileChunkOperationFromResumableUploadRequest(): void
    {
        $uploadId = Uuid::fromString('8bcc36eb-0a16-4a2f-b5f9-a892a81c56c5');

        $resumableUploadRequest = $this->prophesize(ResumableUploadRequestInterface::class);
        $resumableUploadRequest->isUploadComplete()->shouldBeCalledOnce()->willReturn(false);
        $resumableUploadRequest->getContent()->shouldBeCalledOnce()->willReturn('some content');
        $resumableUploadRequest->getContentLength()->shouldBeCalledOnce()->willReturn(12);
        $resumableUploadRequest = $resumableUploadRequest->reveal();

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3UploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');

        $storageService = $this->prophesize(StorageService::class);
        $storageService->getUploadBucketKey(Argument::is($uploadId), Argument::is(1), Argument::is('0123456789abcdef'))->shouldBeCalledOnce()->willReturn('upload-key');

        $factory = $this->buildS3OperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            storageService: $storageService->reveal()
        );

        $operation = $factory->createUploadFileChunkOperationFromResumableUploadRequest($resumableUploadRequest, $uploadId, '0123456789abcdef');

        $this->assertInstanceOf(UploadFileChunkOperation::class, $operation);
        $this->assertSame('upload-bucket', $operation->getUploadBucket());
        $this->assertSame('upload-key', $operation->getUploadKey());
        $this->assertSame('some content', $operation->getContent());
        $this->assertSame(12, $operation->getContentLength());
        $this->assertSame('application/octet-stream', $operation->getMimeType());
    }

    public function testCreateUploadFileChunkOperationFromResumableUploadRequestFailsOnCompleteUpload(): void
    {
        $uploadId = Uuid::fromString('8bcc36eb-0a16-4a2f-b5f9-a892a81c56c5');

        $resumableUploadRequest = $this->prophesize(ResumableUploadRequestInterface::class);
        $resumableUploadRequest->isUploadComplete()->shouldBeCalledOnce()->willReturn(true);
        $resumableUploadRequest = $resumableUploadRequest->reveal();

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is("'UploadFileChunkOperation' requires 'ResumableUploadRequest' to contain partial content, i.e. be a chunked upload request."))->shouldBeCalledOnce()->willReturn($exception);

        $factory = $this->buildS3OperationFactory(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $factory->createUploadFileChunkOperationFromResumableUploadRequest($resumableUploadRequest, $uploadId, '0123456789abcdef');
    }

    public function testCreateUploadFileChunkOperationFromPartialUploadRequest(): void
    {
        $uploadId = Uuid::fromString('8bcc36eb-0a16-4a2f-b5f9-a892a81c56c5');

        $partialUploadRequest = $this->prophesize(PartialUploadRequestInterface::class);
        $partialUploadRequest->getContent()->shouldBeCalledOnce()->willReturn('some content');
        $partialUploadRequest->getContentLength()->shouldBeCalledOnce()->willReturn(12);

        $upload = $this->prophesize(UploadInterface::class);
        $upload->getId()->shouldBeCalledOnce()->willReturn($uploadId);
        $upload->getAlreadyUploadedChunks()->shouldBeCalledOnce()->willReturn(3);

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3UploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');

        $storageService = $this->prophesize(StorageService::class);
        $storageService->getUploadBucketKey(Argument::is($uploadId), Argument::is(4), Argument::is('0123456789abcdef'))->shouldBeCalledOnce()->willReturn('upload-key');

        $factory = $this->buildS3OperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            storageService: $storageService->reveal()
        );

        $operation = $factory->createUploadFileChunkOperationFromPartialUploadRequest($partialUploadRequest->reveal(), $upload->reveal(), '0123456789abcdef');

        $this->assertInstanceOf(UploadFileChunkOperation::class, $operation);
        $this->assertSame('upload-bucket', $operation->getUploadBucket());
        $this->assertSame('upload-key', $operation->getUploadKey());
        $this->assertSame('some content', $operation->getContent());
        $this->assertSame(12, $operation->getContentLength());
        $this->assertSame('application/octet-stream', $operation->getMimeType());
    }

    public function testCreateUploadFileChunkOperationFromUploadFileOperation(): void
    {
        $uploadFileOperation = $this->prophesize(UploadFileOperationInterface::class);
        $uploadFileOperation->getUploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $uploadFileOperation->getUploadKey()->shouldBeCalledOnce()->willReturn('upload-key');
        $uploadFileOperation->getContent()->shouldBeCalledOnce()->willReturn('some content');
        $uploadFileOperation->getContentLength()->shouldBeCalledOnce()->willReturn(12);
        $uploadFileOperation->getMimeType()->shouldBeCalledOnce()->willReturn('text/plain');

        $factory = $this->buildS3OperationFactory();

        $operation = $factory->createUploadFileChunkOperationFromUploadFileOperation($uploadFileOperation->reveal());

        $this->assertInstanceOf(UploadFileChunkOperationInterface::class, $operation);
        $this->assertSame('upload-bucket', $operation->getUploadBucket());
        $this->assertSame('upload-key', $operation->getUploadKey());
        $this->assertSame('some content', $operation->getContent());
        $this->assertSame(12, $operation->getContentLength());
        $this->assertSame('text/plain', $operation->getMimeType());
    }

    public function testCreateMergeFileOperationFromUpload(): void
    {
        $uploadId = Uuid::fromString('8fb0017a-c2af-4140-96fd-c8e7bbe03f5e');
        $uploadTarget = Uuid::fromString('25825901-d004-4c13-a9d9-a0f813de49b4');

        $upload = $this->prophesize(UploadInterface::class);
        $upload->isUploadComplete()->shouldBeCalledOnce()->willReturn(true);
        $upload->getChunkIds()->shouldBeCalledOnce()->willReturn(['aaaaaaaaaaaaaaa1', 'aaaaaaaaaaaaaaa2', 'aaaaaaaaaaaaaaa3']);
        $upload->getId()->shouldBeCalledOnce()->willReturn($uploadId);
        $upload->getUploadTarget()->shouldBeCalledTimes(2)->willReturn($uploadTarget);
        $upload->getExtension()->shouldBeCalledOnce()->willReturn('ext');

        $element = $this->prophesize(NodeElementInterface::class);
        $element = $element->reveal();

        $elementService = $this->prophesize(ElementService::class);
        $elementService->getStorageKeyOfFile(Argument::is($element))->shouldBeCalledOnce()->willReturn(null);

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3UploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $emberNexusConfiguration->getFileS3StorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($uploadTarget))->shouldBeCalledOnce()->willReturn($element);

        $storageService = $this->prophesize(StorageService::class);
        $storageService->getUploadBucketKey(Argument::is($uploadId), Argument::is(1), Argument::is('aaaaaaaaaaaaaaa1'))->shouldBeCalledOnce()->willReturn('upload-key-0001');
        $storageService->getUploadBucketKey(Argument::is($uploadId), Argument::is(2), Argument::is('aaaaaaaaaaaaaaa2'))->shouldBeCalledOnce()->willReturn('upload-key-0002');
        $storageService->getUploadBucketKey(Argument::is($uploadId), Argument::is(3), Argument::is('aaaaaaaaaaaaaaa3'))->shouldBeCalledOnce()->willReturn('upload-key-0003');
        $storageService->getStorageBucketKey(Argument::is($uploadTarget), Argument::is('ext'))->shouldBeCalledOnce()->willReturn('target-key.ext');

        $factory = $this->buildS3OperationFactory(
            elementService: $elementService->reveal(),
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            elementManager: $elementManager->reveal(),
            storageService: $storageService->reveal()
        );

        $mergeFileChunksOperation = $factory->createMergeFileOperationFromUpload($upload->reveal());

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
        $upload->getChunkIds()->shouldBeCalledOnce()->willReturn(['aaaaaaaaaaaaaaa1', 'aaaaaaaaaaaaaaa2']);
        $upload->getId()->shouldBeCalledOnce()->willReturn($uploadId);
        $upload->getUploadTarget()->shouldBeCalledTimes(2)->willReturn($uploadTarget);
        $upload->getExtension()->shouldBeCalledOnce()->willReturn('ext');

        $element = $this->prophesize(NodeElementInterface::class);
        $element = $element->reveal();

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3UploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $emberNexusConfiguration->getFileS3StorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($uploadTarget))->shouldBeCalledOnce()->willReturn($element);

        $storageService = $this->prophesize(StorageService::class);
        $storageService->getUploadBucketKey(Argument::is($uploadId), Argument::is(1), Argument::is('aaaaaaaaaaaaaaa1'))->shouldBeCalledOnce()->willReturn('upload-key-0001');
        $storageService->getUploadBucketKey(Argument::is($uploadId), Argument::is(2), Argument::is('aaaaaaaaaaaaaaa2'))->shouldBeCalledOnce()->willReturn('upload-key-0002');
        $storageService->getStorageBucketKey(Argument::is($uploadTarget), Argument::is('ext'))->shouldBeCalledOnce()->willReturn('target-key.ext');

        $elementService = $this->prophesize(ElementService::class);
        $elementService->getStorageKeyOfFile(Argument::is($element))->shouldBeCalledOnce()->willReturn('target-key.prev');

        $factory = $this->buildS3OperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            elementManager: $elementManager->reveal(),
            elementService: $elementService->reveal(),
            storageService: $storageService->reveal()
        );

        $mergeFileChunksOperation = $factory->createMergeFileOperationFromUpload($upload->reveal());

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

        $factory = $this->buildS3OperationFactory(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $factory->createMergeFileOperationFromUpload($upload->reveal());
    }
}
