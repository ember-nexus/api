<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Type\S3;

use App\Contract\Request\PartialUploadRequestInterface;
use App\Contract\Request\ResumableUploadRequestInterface;
use App\Contract\S3\UploadFileChunkOperationInterface;
use App\Contract\S3\UploadFileOperationInterface;
use App\Contract\UploadInterface;
use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Service\FileService;
use App\Type\S3\UploadFileChunkOperation;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(UploadFileChunkOperationFactory::class)]
class UploadFileChunkOperationFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function buildUploadFileChunkOperationFactory(
        ?EmberNexusConfiguration $emberNexusConfiguration = null,
        ?FileService $fileService = null,
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
    ): UploadFileChunkOperationFactory {
        return new UploadFileChunkOperationFactory(
            $emberNexusConfiguration ?? $this->prophesize(EmberNexusConfiguration::class)->reveal(),
            $fileService ?? $this->prophesize(FileService::class)->reveal(),
            $client400BadContentExceptionFactory ?? $this->prophesize(Client400BadContentExceptionFactory::class)->reveal()
        );
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

        $fileService = $this->prophesize(FileService::class);
        $fileService->getUploadBucketKey(Argument::is($uploadId), Argument::is(1))->shouldBeCalledOnce()->willReturn('upload-key');

        $factory = $this->buildUploadFileChunkOperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            fileService: $fileService->reveal()
        );

        $operation = $factory->createUploadFileChunkOperationFromResumableUploadRequest($resumableUploadRequest, $uploadId);

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

        $factory = $this->buildUploadFileChunkOperationFactory(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $factory->createUploadFileChunkOperationFromResumableUploadRequest($resumableUploadRequest, $uploadId);
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

        $fileService = $this->prophesize(FileService::class);
        $fileService->getUploadBucketKey(Argument::is($uploadId), Argument::is(4))->shouldBeCalledOnce()->willReturn('upload-key');

        $factory = $this->buildUploadFileChunkOperationFactory(
            emberNexusConfiguration: $emberNexusConfiguration->reveal(),
            fileService: $fileService->reveal()
        );

        $operation = $factory->createUploadFileChunkOperationFromPartialUploadRequest($partialUploadRequest->reveal(), $upload->reveal());

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

        $factory = $this->buildUploadFileChunkOperationFactory();

        $operation = $factory->createUploadFileChunkOperationFromUploadFileOperation($uploadFileOperation->reveal());

        $this->assertInstanceOf(UploadFileChunkOperationInterface::class, $operation);
        $this->assertSame('upload-bucket', $operation->getUploadBucket());
        $this->assertSame('upload-key', $operation->getUploadKey());
        $this->assertSame('some content', $operation->getContent());
        $this->assertSame(12, $operation->getContentLength());
        $this->assertSame('text/plain', $operation->getMimeType());
    }
}
