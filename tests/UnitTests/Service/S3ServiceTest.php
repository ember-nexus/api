<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\S3\FileOperationInterface;
use App\Contract\S3\MergeFileChunksOperationInterface;
use App\Exception\Client400BadContentException;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Service\MimeTypeService;
use App\Service\S3Service;
use App\Wrapper\S3ClientWrapper;
use AsyncAws\Core\Stream\ResultStream;
use AsyncAws\S3\Result\CreateMultipartUploadOutput;
use AsyncAws\S3\Result\GetObjectOutput;
use AsyncAws\S3\Result\HeadObjectOutput;
use AsyncAws\S3\Result\ObjectExistsWaiter;
use AsyncAws\S3\S3Client;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(S3Service::class)]
class S3ServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildS3Service(
        ?S3Client $s3Client = null,
        ?UploadFileChunkOperationFactory $fileChunkOperationFactory = null,
        ?S3ClientWrapper $s3ClientWrapper = null,
        ?MimeTypeService $mimeTypeService = null,
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
        ?Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory = null,
    ): S3Service {
        return new S3Service(
            $s3Client ?? $this->prophesize(S3Client::class)->reveal(),
            $fileChunkOperationFactory ?? $this->prophesize(UploadFileChunkOperationFactory::class)->reveal(),
            $s3ClientWrapper ?? $this->prophesize(S3ClientWrapper::class)->reveal(),
            $mimeTypeService ?? $this->prophesize(MimeTypeService::class)->reveal(),
            $client400BadContentExceptionFactory ?? $this->prophesize(Client400BadContentExceptionFactory::class)->reveal(),
            $server500LogicErrorExceptionFactory ?? $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        );
    }

    public function testCreateMultipartUploadFromMergeFileChunksOperation(): void
    {
        $mergeFileChunksOperation = $this->prophesize(MergeFileChunksOperationInterface::class);
        $mergeFileChunksOperation->getStorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $mergeFileChunksOperation->getStorageKey()->shouldBeCalledOnce()->willReturn('storage-key');
        $mergeFileChunksOperation->getUploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $mergeFileChunksOperation->getUploadKeys()->shouldBeCalledOnce()->willReturn(['upload-key-0001']);
        $mergeFileChunksOperation = $mergeFileChunksOperation->reveal();

        $createMultipartUploadOutput = $this->prophesize(CreateMultipartUploadOutput::class);
        $createMultipartUploadOutput->getUploadId()->shouldBeCalledOnce()->willReturn('some id');
        $createMultipartUploadOutput = $createMultipartUploadOutput->reveal();

        $resultStream = $this->prophesize(ResultStream::class);
        $resultStream->getContentAsResource()->shouldBeCalledOnce()->willReturn('some content');

        $getObjectOutput = $this->prophesize(GetObjectOutput::class);
        $getObjectOutput->getBody()->shouldBeCalledOnce()->willReturn($resultStream->reveal());

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(12);

        $mimeTypeService = $this->prophesize(MimeTypeService::class);
        $mimeTypeService->getMimeTypeFromResource(Argument::is('some content'))->shouldBeCalledOnce()->willReturn('text/plain');

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'ContentType' => 'text/plain',
        ]))->shouldBeCalledOnce()->willReturn($createMultipartUploadOutput);
        $s3Client->getObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001',
            'Range' => 'bytes=0-12',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput->reveal());
        $s3Client->headObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            mimeTypeService: $mimeTypeService->reveal()
        );

        $output = $s3Service->createMultipartUploadFromMergeFileChunksOperation($mergeFileChunksOperation);
        $this->assertSame('some id', $output);
    }

    public function testCreateMultipartUploadFromMergeFileChunksOperationThrowsWhenUploadCouldNotBeCreated(): void
    {
        $mergeFileChunksOperation = $this->prophesize(MergeFileChunksOperationInterface::class);
        $mergeFileChunksOperation->getStorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $mergeFileChunksOperation->getStorageKey()->shouldBeCalledOnce()->willReturn('storage-key');
        $mergeFileChunksOperation->getUploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $mergeFileChunksOperation->getUploadKeys()->shouldBeCalledOnce()->willReturn(['upload-key-0001']);
        $mergeFileChunksOperation = $mergeFileChunksOperation->reveal();

        $createMultipartUploadOutput = $this->prophesize(CreateMultipartUploadOutput::class);
        $createMultipartUploadOutput->getUploadId()->shouldBeCalledOnce()->willReturn(null);
        $createMultipartUploadOutput = $createMultipartUploadOutput->reveal();

        $resultStream = $this->prophesize(ResultStream::class);
        $resultStream->getContentAsResource()->shouldBeCalledOnce()->willReturn('some content');

        $getObjectOutput = $this->prophesize(GetObjectOutput::class);
        $getObjectOutput->getBody()->shouldBeCalledOnce()->willReturn($resultStream->reveal());

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(12);

        $mimeTypeService = $this->prophesize(MimeTypeService::class);
        $mimeTypeService->getMimeTypeFromResource(Argument::is('some content'))->shouldBeCalledOnce()->willReturn('text/plain');

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'ContentType' => 'text/plain',
        ]))->shouldBeCalledOnce()->willReturn($createMultipartUploadOutput);
        $s3Client->getObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001',
            'Range' => 'bytes=0-12',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput->reveal());
        $s3Client->headObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory->createFromTemplate('Unable to create multipart upload.')->shouldBeCalledOnce()->willReturn($exception);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            mimeTypeService: $mimeTypeService->reveal(),
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal()
        );

        $this->expectException(Server500LogicErrorException::class);

        $s3Service->createMultipartUploadFromMergeFileChunksOperation($mergeFileChunksOperation);
    }

    public function testDeleteFileSkipsMissingFile(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $objectExistsWaiter = $this->prophesize(ObjectExistsWaiter::class)->reveal();

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->objectExists(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($objectExistsWaiter);
        $s3Client->deleteObject(Argument::any())->shouldNotBeCalled();

        $s3ClientWrapper = $this->prophesize(S3ClientWrapper::class);
        $s3ClientWrapper->getIsSuccessFromObjectExistsWaiter(Argument::is($objectExistsWaiter))->shouldBeCalledOnce()->willReturn(false);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            s3ClientWrapper: $s3ClientWrapper->reveal()
        );

        $s3Service->deleteFile($fileOperation);
    }

    public function testDeleteFileDeletesExistingFile(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $objectExistsWaiter = $this->prophesize(ObjectExistsWaiter::class)->reveal();

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->objectExists(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledTimes(2)->willReturn($objectExistsWaiter);
        $s3Client->deleteObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce();

        $s3ClientWrapper = $this->prophesize(S3ClientWrapper::class);
        $s3ClientWrapper->getIsSuccessFromObjectExistsWaiter(Argument::is($objectExistsWaiter))->shouldBeCalledTimes(2)->willReturn(true, false);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            s3ClientWrapper: $s3ClientWrapper->reveal()
        );

        $s3Service->deleteFile($fileOperation);
    }

    public function testDeleteFileThrowsWhenDeletedFileWasNotDeleted(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $objectExistsWaiter = $this->prophesize(ObjectExistsWaiter::class)->reveal();

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->objectExists(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledTimes(2)->willReturn($objectExistsWaiter);
        $s3Client->deleteObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce();

        $s3ClientWrapper = $this->prophesize(S3ClientWrapper::class);
        $s3ClientWrapper->getIsSuccessFromObjectExistsWaiter(Argument::is($objectExistsWaiter))->shouldBeCalledTimes(2)->willReturn(true, true);

        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory->createFromTemplate('Unable to delete file.')->shouldBeCalledOnce()->willReturn($exception);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            s3ClientWrapper: $s3ClientWrapper->reveal(),
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal()
        );

        $this->expectException(Server500LogicErrorException::class);

        $s3Service->deleteFile($fileOperation);
    }

    public function testExistsFile(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $objectExistsWaiter = $this->prophesize(ObjectExistsWaiter::class)->reveal();

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->objectExists(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($objectExistsWaiter);

        $s3ClientWrapper = $this->prophesize(S3ClientWrapper::class);
        $s3ClientWrapper->getIsSuccessFromObjectExistsWaiter(Argument::is($objectExistsWaiter))->shouldBeCalledOnce()->willReturn(true);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            s3ClientWrapper: $s3ClientWrapper->reveal()
        );

        $output = $s3Service->existsFile($fileOperation);
        $this->assertSame(true, $output);
    }

    public function testGetFile(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $getObjectOutput = $this->prophesize(GetObjectOutput::class)->reveal();

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->getObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $output = $s3Service->getFile($fileOperation);
        $this->assertSame($getObjectOutput, $output);
    }

    public function testGetFileAsResource(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $resultStream = $this->prophesize(ResultStream::class);
        $resultStream->getContentAsResource()->shouldBeCalledOnce()->willReturn('some content');

        $getObjectOutput = $this->prophesize(GetObjectOutput::class);
        $getObjectOutput->getBody()->shouldBeCalledOnce()->willReturn($resultStream->reveal());

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->getObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $resource = $s3Service->getFileAsResource($fileOperation);
        $this->assertSame('some content', $resource);
    }

    public function testGetFileRangeAsResourceClipsResourceLengthToMaxContentLength(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledTimes(2)->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledTimes(2)->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(12345678);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->headObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $resultStream = $this->prophesize(ResultStream::class);
        $resultStream->getContentAsResource()->shouldBeCalledOnce()->willReturn('some content');

        $getObjectOutput = $this->prophesize(GetObjectOutput::class);
        $getObjectOutput->getBody()->shouldBeCalledOnce()->willReturn($resultStream->reveal());

        $s3Client->getObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
            'Range' => 'bytes=0-5000000',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $resource = $s3Service->getFileRangeAsResource($fileOperation, 5000000);
        $this->assertSame('some content', $resource);
    }

    public function testGetFileRangeAsResourceClipsResourceLengthToActualContentLengthWhenTooShort(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledTimes(2)->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledTimes(2)->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(4321);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->headObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $resultStream = $this->prophesize(ResultStream::class);
        $resultStream->getContentAsResource()->shouldBeCalledOnce()->willReturn('some content');

        $getObjectOutput = $this->prophesize(GetObjectOutput::class);
        $getObjectOutput->getBody()->shouldBeCalledOnce()->willReturn($resultStream->reveal());

        $s3Client->getObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
            'Range' => 'bytes=0-4321',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $resource = $s3Service->getFileRangeAsResource($fileOperation, 5000000);
        $this->assertSame('some content', $resource);
    }

    public function testGetFileRangeAsResourceThrowsWhenContentLengthIsNotDeterminable(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(null);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->headObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory->createFromTemplate('Unable to read content length of file.')->shouldBeCalledOnce()->willReturn($exception);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal()
        );

        $this->expectException(Server500LogicErrorException::class);

        $s3Service->getFileRangeAsResource($fileOperation, 5000000);
    }

    public function testGetEtag(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getETag()->shouldBeCalledOnce()->willReturn('some etag');

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->headObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $etag = $s3Service->getEtag($fileOperation);
        $this->assertSame('some etag', $etag);
    }

    public function testGetEtagThrowsWhenEtagIsNull(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getETag()->shouldBeCalledOnce()->willReturn(null);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->headObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory->createFromTemplate('Unable to retrieve file.')->shouldBeCalledOnce()->willReturn($exception);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal()
        );

        $this->expectException(Server500LogicErrorException::class);

        $s3Service->getEtag($fileOperation);
    }

    public function testGetMimeTypeFromFile(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledTimes(2)->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledTimes(2)->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(12345678);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->headObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $resultStream = $this->prophesize(ResultStream::class);
        $resultStream->getContentAsResource()->shouldBeCalledOnce()->willReturn('some content');

        $getObjectOutput = $this->prophesize(GetObjectOutput::class);
        $getObjectOutput->getBody()->shouldBeCalledOnce()->willReturn($resultStream->reveal());

        $s3Client->getObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
            'Range' => 'bytes=0-5242880',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput->reveal());

        $mimeTypeService = $this->prophesize(MimeTypeService::class);
        $mimeTypeService->getMimeTypeFromResource(Argument::is('some content'))->shouldBeCalledOnce()->willReturn('text/plain');

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            mimeTypeService: $mimeTypeService->reveal()
        );

        $mimeType = $s3Service->getMimeTypeFromFile($fileOperation);
        $this->assertSame('text/plain', $mimeType);
    }

    public function testGetMimeTypeFromMergeFileChunksOperation(): void
    {
        $mergeFileChunksOperation = $this->prophesize(MergeFileChunksOperationInterface::class);
        $mergeFileChunksOperation->getUploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $mergeFileChunksOperation->getUploadKeys()->shouldBeCalledOnce()->willReturn(['upload-key-0001.bin', 'upload-key-0002.bin']);
        $mergeFileChunksOperation = $mergeFileChunksOperation->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(12345678);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->headObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001.bin',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $resultStream = $this->prophesize(ResultStream::class);
        $resultStream->getContentAsResource()->shouldBeCalledOnce()->willReturn('some content');

        $getObjectOutput = $this->prophesize(GetObjectOutput::class);
        $getObjectOutput->getBody()->shouldBeCalledOnce()->willReturn($resultStream->reveal());

        $s3Client->getObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001.bin',
            'Range' => 'bytes=0-5242880',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput->reveal());

        $mimeTypeService = $this->prophesize(MimeTypeService::class);
        $mimeTypeService->getMimeTypeFromResource(Argument::is('some content'))->shouldBeCalledOnce()->willReturn('text/plain');

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            mimeTypeService: $mimeTypeService->reveal()
        );

        $mimeType = $s3Service->getMimeTypeFromMergeFileChunksOperation($mergeFileChunksOperation);
        $this->assertSame('text/plain', $mimeType);
    }

    public function testGetMimeTypeFromMergeFileChunksOperationThrowsWhenMergeContainsNoUploadChunk(): void
    {
        $mergeFileChunksOperation = $this->prophesize(MergeFileChunksOperationInterface::class);
        $mergeFileChunksOperation->getUploadBucket()->shouldNotBeCalled();
        $mergeFileChunksOperation->getUploadKeys()->shouldBeCalledOnce()->willReturn([]);
        $mergeFileChunksOperation = $mergeFileChunksOperation->reveal();

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is('Creating a single file from multiple uploaded chunks requires at least one chunk to be present, got none.'))->shouldBeCalledOnce()->willReturn($exception);

        $s3Service = $this->buildS3Service(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);
        $s3Service->getMimeTypeFromMergeFileChunksOperation($mergeFileChunksOperation);
    }
}
