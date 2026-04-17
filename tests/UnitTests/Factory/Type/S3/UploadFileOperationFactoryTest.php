<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Type\S3;

use App\Contract\NodeElementInterface;
use App\Contract\Request\ResumableUploadRequestInterface;
use App\Exception\Client400BadContentException;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\UploadFileOperationFactory;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\FileService;
use App\Type\S3\UploadFileOperation;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(UploadFileOperationFactory::class)]
class UploadFileOperationFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function buildUploadFileOperationFactory(
        ?EmberNexusConfiguration $emberNexusConfiguration = null,
        ?ElementManager $elementManager = null,
        ?ElementService $elementService = null,
        ?FileService $fileService = null,
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
        ?Server500LogicErrorExceptionFactory $server500LogicExceptionFactory = null,
    ): UploadFileOperationFactory {
        return new UploadFileOperationFactory(
            $emberNexusConfiguration ?? $this->prophesize(EmberNexusConfiguration::class)->reveal(),
            $elementManager ?? $this->prophesize(ElementManager::class)->reveal(),
            $elementService ?? $this->prophesize(ElementService::class)->reveal(),
            $fileService ?? $this->prophesize(FileService::class)->reveal(),
            $client400BadContentExceptionFactory ?? $this->prophesize(Client400BadContentExceptionFactory::class)->reveal(),
            $server500LogicExceptionFactory ?? $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );
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
        $element->hasProperty(Argument::is('file'))->shouldBeCalledOnce()->willReturn(false);
        $element = $element->reveal();

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($elementId))->shouldBeCalledOnce()->willReturn($element);

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3UploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $emberNexusConfiguration->getFileS3StorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');

        $fileService = $this->prophesize(FileService::class);
        $fileService->getUploadBucketKey(Argument::is($elementId), Argument::is(0))->shouldBeCalledOnce()->willReturn('upload-key');
        $fileService->getStorageBucketKey(Argument::is($elementId), Argument::is('txt'))->shouldBeCalledOnce()->willReturn('storage-key.txt');
        $fileService->getMimeTypeFromResource(Argument::is('some content'))->shouldBeCalledOnce()->willReturn('text/plain');

        $uploadFileOperationFactory = $this->buildUploadFileOperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            elementManager: $elementManager->reveal(),
            fileService: $fileService->reveal()
        );

        $uploadFileOperation = $uploadFileOperationFactory->createUploadFileOperationFromResumableUploadRequest($resumableUploadRequest->reveal());

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
        $element->hasProperty(Argument::is('file'))->shouldBeCalledOnce()->willReturn(true);
        $element = $element->reveal();

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElementOrFail(Argument::is($elementId))->shouldBeCalledOnce()->willReturn($element);

        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getFileS3UploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $emberNexusConfiguration->getFileS3StorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');

        $fileService = $this->prophesize(FileService::class);
        $fileService->getStorageBucketKey(Argument::is($elementId), Argument::is('prev'))->shouldBeCalledOnce()->willReturn('storage-key.prev');
        $fileService->getUploadBucketKey(Argument::is($elementId), Argument::is(0))->shouldBeCalledOnce()->willReturn('upload-key');
        $fileService->getStorageBucketKey(Argument::is($elementId), Argument::is('txt'))->shouldBeCalledOnce()->willReturn('storage-key.txt');
        $fileService->getMimeTypeFromResource(Argument::is('some content'))->shouldBeCalledOnce()->willReturn('text/plain');

        $elementService = $this->prophesize(ElementService::class);
        $elementService->getFileNameExtension(Argument::is($element))->shouldBeCalledOnce()->willReturn('prev');

        $uploadFileOperationFactory = $this->buildUploadFileOperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            elementManager: $elementManager->reveal(),
            elementService: $elementService->reveal(),
            fileService: $fileService->reveal()
        );

        $uploadFileOperation = $uploadFileOperationFactory->createUploadFileOperationFromResumableUploadRequest($resumableUploadRequest->reveal());

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

        $uploadFileOperationFactory = $this->buildUploadFileOperationFactory(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $uploadFileOperationFactory->createUploadFileOperationFromResumableUploadRequest($resumableUploadRequest->reveal());
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

        $fileService = $this->prophesize(FileService::class);
        $fileService->getUploadBucketKey(Argument::is($elementId), Argument::is(0))->shouldBeCalledOnce()->willReturn('upload-key');
        $fileService->getStorageBucketKey(Argument::is($elementId), Argument::is('ext'))->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileService->getMimeTypeFromResource(Argument::is('some content'))->shouldBeCalledOnce()->willReturn('text/plain');

        $elementService = $this->prophesize(ElementService::class);
        $elementService->getFileNameExtension(Argument::is($element))->shouldBeCalledOnce()->willReturn('ext');

        $uploadFileOperationFactory = $this->buildUploadFileOperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            elementService: $elementService->reveal(),
            fileService: $fileService->reveal()
        );

        $uploadFileOperation = $uploadFileOperationFactory->createUploadFileOperationFromElementAndResource($element, 'some content');

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

        $uploadFileOperationFactory = $this->buildUploadFileOperationFactory(
            server500LogicExceptionFactory: $server500LogicExceptionFactory->reveal()
        );

        $this->expectException(Server500LogicErrorException::class);

        $uploadFileOperationFactory->createUploadFileOperationFromElementAndResource($element, 'some content');
    }
}
