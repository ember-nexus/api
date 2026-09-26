<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\S3\FileOperationInterface;
use App\Contract\S3\MergeFileChunksOperationInterface;
use App\Contract\S3\S3TechnicalLimitsInterface;
use App\Contract\S3\UploadFileChunkOperationInterface;
use App\Contract\S3\UploadFileOperationInterface;
use App\Exception\Client400BadContentException;
use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Service\MimeTypeService;
use App\Service\S3Service;
use App\Service\S3TechnicalLimitsValidator;
use App\Type\S3\S3TechnicalLimits;
use App\Wrapper\S3ClientWrapper;
use AsyncAws\Core\Stream\ResultStream;
use AsyncAws\S3\Result\AbortMultipartUploadOutput;
use AsyncAws\S3\Result\CopyObjectOutput;
use AsyncAws\S3\Result\CreateMultipartUploadOutput;
use AsyncAws\S3\Result\GetObjectOutput;
use AsyncAws\S3\Result\HeadObjectOutput;
use AsyncAws\S3\Result\ObjectExistsWaiter;
use AsyncAws\S3\Result\UploadPartCopyOutput;
use AsyncAws\S3\Result\UploadPartOutput;
use AsyncAws\S3\S3Client;
use AsyncAws\S3\ValueObject\CopyPartResult;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
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
        ?S3TechnicalLimitsValidator $s3TechnicalLimitsValidator = null,
        ?S3TechnicalLimitsInterface $s3TechnicalLimits = null,
        ?LoggerInterface $logger = null,
        ?int $multipartUploadThresholdInBytes = null,
        ?int $multipartUploadPartSizeInBytes = null,
    ): S3Service {
        $s3TechnicalLimitsValidator ??= $this->prophesize(S3TechnicalLimitsValidator::class)->reveal();

        return new S3Service(
            $s3Client ?? $this->prophesize(S3Client::class)->reveal(),
            $fileChunkOperationFactory ?? $this->prophesize(UploadFileChunkOperationFactory::class)->reveal(),
            $s3ClientWrapper ?? $this->prophesize(S3ClientWrapper::class)->reveal(),
            $mimeTypeService ?? $this->prophesize(MimeTypeService::class)->reveal(),
            $client400BadContentExceptionFactory ?? $this->prophesize(Client400BadContentExceptionFactory::class)->reveal(),
            $server500LogicErrorExceptionFactory ?? $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
            $s3TechnicalLimits ?? new S3TechnicalLimits(),
            $logger ?? $this->prophesize(LoggerInterface::class)->reveal(),
            $s3TechnicalLimitsValidator,
            $multipartUploadThresholdInBytes ?? S3Service::MULTIPART_UPLOAD_THRESHOLD_IN_BYTES,
            $multipartUploadPartSizeInBytes ?? S3Service::MULTIPART_UPLOAD_PART_SIZE_IN_BYTES,
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
            'Range' => 'bytes=0-11',
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
            'Range' => 'bytes=0-11',
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

    public function testCreateUploadPartsFromMergeFileChunksOperation(): void
    {
        $mergeFileChunksOperation = $this->prophesize(MergeFileChunksOperationInterface::class);
        $mergeFileChunksOperation->getStorageBucket()->shouldBeCalledTimes(3)->willReturn('storage-bucket');
        $mergeFileChunksOperation->getStorageKey()->shouldBeCalledTimes(3)->willReturn('storage-key');
        $mergeFileChunksOperation->getUploadBucket()->shouldBeCalledTimes(3)->willReturn('upload-bucket');
        $mergeFileChunksOperation->getUploadKeys()->shouldBeCalledOnce()->willReturn(['upload-key-0001', 'upload-key-0002', 'upload-key-0003']);
        $mergeFileChunksOperation = $mergeFileChunksOperation->reveal();

        $copyPartResult1 = new CopyPartResult(['ETag' => 'etag-1']);
        $uploadPartCopyOutput1 = $this->prophesize(UploadPartCopyOutput::class);
        $uploadPartCopyOutput1->getCopyPartResult()->shouldBeCalledOnce()->willReturn($copyPartResult1);

        $copyPartResult2 = new CopyPartResult(['ETag' => 'etag-2']);
        $uploadPartCopyOutput2 = $this->prophesize(UploadPartCopyOutput::class);
        $uploadPartCopyOutput2->getCopyPartResult()->shouldBeCalledOnce()->willReturn($copyPartResult2);

        $copyPartResult3 = new CopyPartResult(['ETag' => 'etag-3']);
        $uploadPartCopyOutput3 = $this->prophesize(UploadPartCopyOutput::class);
        $uploadPartCopyOutput3->getCopyPartResult()->shouldBeCalledOnce()->willReturn($copyPartResult3);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->uploadPartCopy(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'PartNumber' => 1,
            'CopySource' => 'upload-bucket/upload-key-0001',
        ]))->shouldBeCalledOnce()->willReturn($uploadPartCopyOutput1->reveal());
        $s3Client->uploadPartCopy(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'PartNumber' => 2,
            'CopySource' => 'upload-bucket/upload-key-0002',
        ]))->shouldBeCalledOnce()->willReturn($uploadPartCopyOutput2->reveal());
        $s3Client->uploadPartCopy(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'PartNumber' => 3,
            'CopySource' => 'upload-bucket/upload-key-0003',
        ]))->shouldBeCalledOnce()->willReturn($uploadPartCopyOutput3->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
        );

        $parts = $s3Service->createUploadPartsFromMergeFileChunksOperation($mergeFileChunksOperation, 'upload-id');
        $this->assertSame([
            [
                'PartNumber' => 1,
                'ETag' => 'etag-1',
            ],
            [
                'PartNumber' => 2,
                'ETag' => 'etag-2',
            ],
            [
                'PartNumber' => 3,
                'ETag' => 'etag-3',
            ],
        ], $parts);
    }

    public function testCreateUploadPartsFromMergeFileChunksOperationThrowsWhenCopyPartResultIsNull(): void
    {
        $mergeFileChunksOperation = $this->prophesize(MergeFileChunksOperationInterface::class);
        $mergeFileChunksOperation->getStorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $mergeFileChunksOperation->getStorageKey()->shouldBeCalledOnce()->willReturn('storage-key');
        $mergeFileChunksOperation->getUploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $mergeFileChunksOperation->getUploadKeys()->shouldBeCalledOnce()->willReturn(['upload-key-0001']);
        $mergeFileChunksOperation = $mergeFileChunksOperation->reveal();

        $uploadPartCopyOutput1 = $this->prophesize(UploadPartCopyOutput::class);
        $uploadPartCopyOutput1->getCopyPartResult()->shouldBeCalledOnce()->willReturn(null);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->uploadPartCopy(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'PartNumber' => 1,
            'CopySource' => 'upload-bucket/upload-key-0001',
        ]))->shouldBeCalledOnce()->willReturn($uploadPartCopyOutput1->reveal());

        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory->createFromTemplate('Unable to read copy part result.')->shouldBeCalledOnce()->willReturn($exception);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal()
        );

        $this->expectException(Server500LogicErrorException::class);

        $s3Service->createUploadPartsFromMergeFileChunksOperation($mergeFileChunksOperation, 'upload-id');
    }

    public function testCreateUploadPartsFromMergeFileChunksOperationThrowsWhenEtagIsNull(): void
    {
        $mergeFileChunksOperation = $this->prophesize(MergeFileChunksOperationInterface::class);
        $mergeFileChunksOperation->getStorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $mergeFileChunksOperation->getStorageKey()->shouldBeCalledOnce()->willReturn('storage-key');
        $mergeFileChunksOperation->getUploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $mergeFileChunksOperation->getUploadKeys()->shouldBeCalledOnce()->willReturn(['upload-key-0001']);
        $mergeFileChunksOperation = $mergeFileChunksOperation->reveal();

        $copyPartResult1 = new CopyPartResult([]);
        $uploadPartCopyOutput1 = $this->prophesize(UploadPartCopyOutput::class);
        $uploadPartCopyOutput1->getCopyPartResult()->shouldBeCalledOnce()->willReturn($copyPartResult1);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->uploadPartCopy(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'PartNumber' => 1,
            'CopySource' => 'upload-bucket/upload-key-0001',
        ]))->shouldBeCalledOnce()->willReturn($uploadPartCopyOutput1->reveal());

        $exception = $this->prophesize(Server500LogicErrorException::class)->reveal();

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory->createFromTemplate('Unable to read etag of copy part result.')->shouldBeCalledOnce()->willReturn($exception);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal()
        );

        $this->expectException(Server500LogicErrorException::class);

        $s3Service->createUploadPartsFromMergeFileChunksOperation($mergeFileChunksOperation, 'upload-id');
    }

    public function testMergeFileChunks(): void
    {
        $mergeFileChunksOperation = $this->prophesize(MergeFileChunksOperationInterface::class);
        $mergeFileChunksOperation->getStorageBucket()->shouldBeCalledTimes(6)->willReturn('storage-bucket');
        $mergeFileChunksOperation->getStorageKey()->shouldBeCalledTimes(6)->willReturn('storage-key');
        $mergeFileChunksOperation->getUploadBucket()->shouldBeCalledTimes(4)->willReturn('upload-bucket');
        $mergeFileChunksOperation->getUploadKeys()->shouldBeCalledTimes(2)->willReturn(['upload-key-0001', 'upload-key-0002', 'upload-key-0003']);
        $mergeFileChunksOperation->getPreviousStorageKey()->shouldBeCalledOnce()->willReturn(null);
        $mergeFileChunksOperation = $mergeFileChunksOperation->reveal();

        $createMultipartUploadOutput = $this->prophesize(CreateMultipartUploadOutput::class);
        $createMultipartUploadOutput->getUploadId()->shouldBeCalledOnce()->willReturn('upload-id');
        $createMultipartUploadOutput = $createMultipartUploadOutput->reveal();

        $copyPartResult1 = new CopyPartResult(['ETag' => 'etag-1']);
        $uploadPartCopyOutput1 = $this->prophesize(UploadPartCopyOutput::class);
        $uploadPartCopyOutput1->getCopyPartResult()->shouldBeCalledOnce()->willReturn($copyPartResult1);

        $copyPartResult2 = new CopyPartResult(['ETag' => 'etag-2']);
        $uploadPartCopyOutput2 = $this->prophesize(UploadPartCopyOutput::class);
        $uploadPartCopyOutput2->getCopyPartResult()->shouldBeCalledOnce()->willReturn($copyPartResult2);

        $copyPartResult3 = new CopyPartResult(['ETag' => 'etag-3']);
        $uploadPartCopyOutput3 = $this->prophesize(UploadPartCopyOutput::class);
        $uploadPartCopyOutput3->getCopyPartResult()->shouldBeCalledOnce()->willReturn($copyPartResult3);

        $headObjectOutput1 = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput1->getContentLength()->shouldBeCalledOnce()->willReturn(12);

        $resultStream = $this->prophesize(ResultStream::class);
        $resultStream->getContentAsResource()->shouldBeCalledOnce()->willReturn('some content');

        $getObjectOutput1 = $this->prophesize(GetObjectOutput::class);
        $getObjectOutput1->getBody()->shouldBeCalledOnce()->willReturn($resultStream->reveal());

        $headObjectOutput2 = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput2->getContentLength()->shouldBeCalledOnce()->willReturn(12345678);

        $mimeTypeService = $this->prophesize(MimeTypeService::class);
        $mimeTypeService->getMimeTypeFromResource(Argument::is('some content'))->shouldBeCalledOnce()->willReturn('text/plain');

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'ContentType' => 'text/plain',
        ]))->shouldBeCalledOnce()->willReturn($createMultipartUploadOutput);
        $s3Client->uploadPartCopy(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'PartNumber' => 1,
            'CopySource' => 'upload-bucket/upload-key-0001',
        ]))->shouldBeCalledOnce()->willReturn($uploadPartCopyOutput1->reveal());
        $s3Client->uploadPartCopy(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'PartNumber' => 2,
            'CopySource' => 'upload-bucket/upload-key-0002',
        ]))->shouldBeCalledOnce()->willReturn($uploadPartCopyOutput2->reveal());
        $s3Client->uploadPartCopy(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'PartNumber' => 3,
            'CopySource' => 'upload-bucket/upload-key-0003',
        ]))->shouldBeCalledOnce()->willReturn($uploadPartCopyOutput3->reveal());
        $s3Client->completeMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'MultipartUpload' => [
                'Parts' => [
                    [
                        'PartNumber' => 1,
                        'ETag' => 'etag-1',
                    ],
                    [
                        'PartNumber' => 2,
                        'ETag' => 'etag-2',
                    ],
                    [
                        'PartNumber' => 3,
                        'ETag' => 'etag-3',
                    ],
                ],
            ],
        ]))->shouldBeCalledOnce();
        $s3Client->headObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput1->reveal());
        $s3Client->getObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001',
            'Range' => 'bytes=0-11',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput1->reveal());
        $s3Client->headObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput2->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            mimeTypeService: $mimeTypeService->reveal()
        );

        $contentLength = $s3Service->mergeFileChunks($mergeFileChunksOperation);
        $this->assertSame(12345678, $contentLength);
    }

    public function testMergeFileChunksAbortsUploadWhenExceptionIsThrownDuringUpload(): void
    {
        $mergeFileChunksOperation = $this->prophesize(MergeFileChunksOperationInterface::class);
        $mergeFileChunksOperation->getStorageBucket()->shouldBeCalledTimes(6)->willReturn('storage-bucket');
        $mergeFileChunksOperation->getStorageKey()->shouldBeCalledTimes(6)->willReturn('storage-key');
        $mergeFileChunksOperation->getUploadBucket()->shouldBeCalledTimes(4)->willReturn('upload-bucket');
        $mergeFileChunksOperation->getUploadKeys()->shouldBeCalledTimes(2)->willReturn(['upload-key-0001', 'upload-key-0002', 'upload-key-0003']);
        $mergeFileChunksOperation->getPreviousStorageKey()->shouldNotBeCalled();
        $mergeFileChunksOperation = $mergeFileChunksOperation->reveal();

        $createMultipartUploadOutput = $this->prophesize(CreateMultipartUploadOutput::class);
        $createMultipartUploadOutput->getUploadId()->shouldBeCalledOnce()->willReturn('upload-id');
        $createMultipartUploadOutput = $createMultipartUploadOutput->reveal();

        $copyPartResult1 = new CopyPartResult(['ETag' => 'etag-1']);
        $uploadPartCopyOutput1 = $this->prophesize(UploadPartCopyOutput::class);
        $uploadPartCopyOutput1->getCopyPartResult()->shouldBeCalledOnce()->willReturn($copyPartResult1);

        $copyPartResult2 = new CopyPartResult(['ETag' => 'etag-2']);
        $uploadPartCopyOutput2 = $this->prophesize(UploadPartCopyOutput::class);
        $uploadPartCopyOutput2->getCopyPartResult()->shouldBeCalledOnce()->willReturn($copyPartResult2);

        $copyPartResult3 = new CopyPartResult(['ETag' => 'etag-3']);
        $uploadPartCopyOutput3 = $this->prophesize(UploadPartCopyOutput::class);
        $uploadPartCopyOutput3->getCopyPartResult()->shouldBeCalledOnce()->willReturn($copyPartResult3);

        $headObjectOutput1 = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput1->getContentLength()->shouldBeCalledOnce()->willReturn(12);

        $resultStream = $this->prophesize(ResultStream::class);
        $resultStream->getContentAsResource()->shouldBeCalledOnce()->willReturn('some content');

        $getObjectOutput1 = $this->prophesize(GetObjectOutput::class);
        $getObjectOutput1->getBody()->shouldBeCalledOnce()->willReturn($resultStream->reveal());

        $mimeTypeService = $this->prophesize(MimeTypeService::class);
        $mimeTypeService->getMimeTypeFromResource(Argument::is('some content'))->shouldBeCalledOnce()->willReturn('text/plain');

        $originalException = new Exception('some message');

        $finalException = $this->prophesize(Server500LogicErrorException::class)->reveal();

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory->createFromTemplate(Argument::is("Caught exception 'some message' during multipart upload."), Argument::is([]), Argument::is($originalException))->shouldBeCalledOnce()->willReturn($finalException);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'ContentType' => 'text/plain',
        ]))->shouldBeCalledOnce()->willReturn($createMultipartUploadOutput);
        $s3Client->uploadPartCopy(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'PartNumber' => 1,
            'CopySource' => 'upload-bucket/upload-key-0001',
        ]))->shouldBeCalledOnce()->willReturn($uploadPartCopyOutput1->reveal());
        $s3Client->uploadPartCopy(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'PartNumber' => 2,
            'CopySource' => 'upload-bucket/upload-key-0002',
        ]))->shouldBeCalledOnce()->willReturn($uploadPartCopyOutput2->reveal());
        $s3Client->uploadPartCopy(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'PartNumber' => 3,
            'CopySource' => 'upload-bucket/upload-key-0003',
        ]))->shouldBeCalledOnce()->willReturn($uploadPartCopyOutput3->reveal());
        $s3Client->completeMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'MultipartUpload' => [
                'Parts' => [
                    [
                        'PartNumber' => 1,
                        'ETag' => 'etag-1',
                    ],
                    [
                        'PartNumber' => 2,
                        'ETag' => 'etag-2',
                    ],
                    [
                        'PartNumber' => 3,
                        'ETag' => 'etag-3',
                    ],
                ],
            ],
        ]))->shouldBeCalledOnce()->willThrow($originalException);
        $s3Client->headObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput1->reveal());
        $s3Client->getObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001',
            'Range' => 'bytes=0-11',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput1->reveal());
        $s3Client->abortMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
        ]))->shouldBeCalledOnce();

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            mimeTypeService: $mimeTypeService->reveal(),
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal()
        );

        $this->expectException(Server500LogicErrorException::class);

        $s3Service->mergeFileChunks($mergeFileChunksOperation);
    }

    public function testMergeFileChunksDeletesPreviousFileIfAvailable(): void
    {
        $mergeFileChunksOperation = $this->prophesize(MergeFileChunksOperationInterface::class);
        $mergeFileChunksOperation->getStorageBucket()->shouldBeCalledTimes(5)->willReturn('storage-bucket');
        $mergeFileChunksOperation->getStorageKey()->shouldBeCalledTimes(5)->willReturn('storage-key');
        $mergeFileChunksOperation->getUploadBucket()->shouldBeCalledTimes(2)->willReturn('upload-bucket');
        $mergeFileChunksOperation->getUploadKeys()->shouldBeCalledTimes(2)->willReturn(['upload-key-0001']);
        $mergeFileChunksOperation->getPreviousStorageKey()->shouldBeCalledOnce()->willReturn('storage-key.prev');
        $mergeFileChunksOperation = $mergeFileChunksOperation->reveal();

        $createMultipartUploadOutput = $this->prophesize(CreateMultipartUploadOutput::class);
        $createMultipartUploadOutput->getUploadId()->shouldBeCalledOnce()->willReturn('upload-id');
        $createMultipartUploadOutput = $createMultipartUploadOutput->reveal();

        $copyPartResult1 = new CopyPartResult(['ETag' => 'etag-1']);
        $uploadPartCopyOutput1 = $this->prophesize(UploadPartCopyOutput::class);
        $uploadPartCopyOutput1->getCopyPartResult()->shouldBeCalledOnce()->willReturn($copyPartResult1);

        $headObjectOutput1 = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput1->getContentLength()->shouldBeCalledOnce()->willReturn(12);

        $resultStream = $this->prophesize(ResultStream::class);
        $resultStream->getContentAsResource()->shouldBeCalledOnce()->willReturn('some content');

        $getObjectOutput1 = $this->prophesize(GetObjectOutput::class);
        $getObjectOutput1->getBody()->shouldBeCalledOnce()->willReturn($resultStream->reveal());

        $headObjectOutput2 = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput2->getContentLength()->shouldBeCalledOnce()->willReturn(12345678);

        $mimeTypeService = $this->prophesize(MimeTypeService::class);
        $mimeTypeService->getMimeTypeFromResource(Argument::is('some content'))->shouldBeCalledOnce()->willReturn('text/plain');

        $objectExistsWaiter = $this->prophesize(ObjectExistsWaiter::class)->reveal();

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'ContentType' => 'text/plain',
        ]))->shouldBeCalledOnce()->willReturn($createMultipartUploadOutput);
        $s3Client->uploadPartCopy(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'PartNumber' => 1,
            'CopySource' => 'upload-bucket/upload-key-0001',
        ]))->shouldBeCalledOnce()->willReturn($uploadPartCopyOutput1->reveal());
        $s3Client->completeMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'MultipartUpload' => [
                'Parts' => [
                    [
                        'PartNumber' => 1,
                        'ETag' => 'etag-1',
                    ],
                ],
            ],
        ]))->shouldBeCalledOnce();
        $s3Client->headObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput1->reveal());
        $s3Client->getObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001',
            'Range' => 'bytes=0-11',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput1->reveal());
        $s3Client->headObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput2->reveal());
        $s3Client->objectExists(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.prev',
        ]))->shouldBeCalledTimes(2)->willReturn($objectExistsWaiter);
        $s3Client->deleteObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.prev',
        ]))->shouldBeCalledOnce();

        $s3ClientWrapper = $this->prophesize(S3ClientWrapper::class);
        $s3ClientWrapper->getIsSuccessFromObjectExistsWaiter(Argument::is($objectExistsWaiter))->shouldBeCalledTimes(2)->willReturn(true, false);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            mimeTypeService: $mimeTypeService->reveal(),
            s3ClientWrapper: $s3ClientWrapper->reveal()
        );

        $contentLength = $s3Service->mergeFileChunks($mergeFileChunksOperation);
        $this->assertSame(12345678, $contentLength);
    }

    /**
     * A same-extension replace reuses the storage key, so the "previous" file is the one just written and must
     * not be deleted.
     */
    public function testMergeFileChunksDoesNotDeletePreviousFileWhenItIsTheSameAsTheNewOne(): void
    {
        $mergeFileChunksOperation = $this->prophesize(MergeFileChunksOperationInterface::class);
        $mergeFileChunksOperation->getStorageBucket()->shouldBeCalledTimes(4)->willReturn('storage-bucket');
        $mergeFileChunksOperation->getStorageKey()->shouldBeCalledTimes(5)->willReturn('storage-key');
        $mergeFileChunksOperation->getUploadBucket()->shouldBeCalledTimes(2)->willReturn('upload-bucket');
        $mergeFileChunksOperation->getUploadKeys()->shouldBeCalledTimes(2)->willReturn(['upload-key-0001']);
        $mergeFileChunksOperation->getPreviousStorageKey()->shouldBeCalledOnce()->willReturn('storage-key');
        $mergeFileChunksOperation = $mergeFileChunksOperation->reveal();

        $createMultipartUploadOutput = $this->prophesize(CreateMultipartUploadOutput::class);
        $createMultipartUploadOutput->getUploadId()->shouldBeCalledOnce()->willReturn('upload-id');
        $createMultipartUploadOutput = $createMultipartUploadOutput->reveal();

        $copyPartResult1 = new CopyPartResult(['ETag' => 'etag-1']);
        $uploadPartCopyOutput1 = $this->prophesize(UploadPartCopyOutput::class);
        $uploadPartCopyOutput1->getCopyPartResult()->shouldBeCalledOnce()->willReturn($copyPartResult1);

        $headObjectOutput1 = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput1->getContentLength()->shouldBeCalledOnce()->willReturn(12);

        $resultStream = $this->prophesize(ResultStream::class);
        $resultStream->getContentAsResource()->shouldBeCalledOnce()->willReturn('some content');

        $getObjectOutput1 = $this->prophesize(GetObjectOutput::class);
        $getObjectOutput1->getBody()->shouldBeCalledOnce()->willReturn($resultStream->reveal());

        $headObjectOutput2 = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput2->getContentLength()->shouldBeCalledOnce()->willReturn(12345678);

        $mimeTypeService = $this->prophesize(MimeTypeService::class);
        $mimeTypeService->getMimeTypeFromResource(Argument::is('some content'))->shouldBeCalledOnce()->willReturn('text/plain');

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'ContentType' => 'text/plain',
        ]))->shouldBeCalledOnce()->willReturn($createMultipartUploadOutput);
        $s3Client->uploadPartCopy(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'PartNumber' => 1,
            'CopySource' => 'upload-bucket/upload-key-0001',
        ]))->shouldBeCalledOnce()->willReturn($uploadPartCopyOutput1->reveal());
        $s3Client->completeMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'upload-id',
            'MultipartUpload' => [
                'Parts' => [
                    [
                        'PartNumber' => 1,
                        'ETag' => 'etag-1',
                    ],
                ],
            ],
        ]))->shouldBeCalledOnce();
        $s3Client->headObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput1->reveal());
        $s3Client->getObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001',
            'Range' => 'bytes=0-11',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput1->reveal());
        $s3Client->headObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput2->reveal());
        $s3Client->objectExists(Argument::any())->shouldNotBeCalled();
        $s3Client->deleteObject(Argument::any())->shouldNotBeCalled();

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            mimeTypeService: $mimeTypeService->reveal()
        );

        $contentLength = $s3Service->mergeFileChunks($mergeFileChunksOperation);
        $this->assertSame(12345678, $contentLength);
    }

    public function testDeleteFileChunks(): void
    {
        $mergeFileChunksOperation = $this->prophesize(MergeFileChunksOperationInterface::class);
        $mergeFileChunksOperation->getUploadKeys()->shouldBeCalledOnce()->willReturn(['upload-key-0001', 'upload-key-0002', 'upload-key-0003']);
        $mergeFileChunksOperation->getUploadBucket()->shouldBeCalledTimes(3)->willReturn('upload-bucket');
        $mergeFileChunksOperation = $mergeFileChunksOperation->reveal();

        $objectExistsWaiter1 = $this->prophesize(ObjectExistsWaiter::class)->reveal();
        $objectExistsWaiter2 = $this->prophesize(ObjectExistsWaiter::class)->reveal();
        $objectExistsWaiter3 = $this->prophesize(ObjectExistsWaiter::class)->reveal();

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->objectExists(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001',
        ]))->shouldBeCalledTimes(2)->willReturn($objectExistsWaiter1);
        $s3Client->deleteObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0001',
        ]))->shouldBeCalledOnce();

        $s3Client->objectExists(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0002',
        ]))->shouldBeCalledTimes(2)->willReturn($objectExistsWaiter2);
        $s3Client->deleteObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0002',
        ]))->shouldBeCalledOnce();

        $s3Client->objectExists(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0003',
        ]))->shouldBeCalledTimes(2)->willReturn($objectExistsWaiter3);
        $s3Client->deleteObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key-0003',
        ]))->shouldBeCalledOnce();

        $s3ClientWrapper = $this->prophesize(S3ClientWrapper::class);
        $s3ClientWrapper->getIsSuccessFromObjectExistsWaiter(Argument::is($objectExistsWaiter1))->shouldBeCalledTimes(2)->willReturn(true, false);
        $s3ClientWrapper->getIsSuccessFromObjectExistsWaiter(Argument::is($objectExistsWaiter2))->shouldBeCalledTimes(2)->willReturn(true, false);
        $s3ClientWrapper->getIsSuccessFromObjectExistsWaiter(Argument::is($objectExistsWaiter3))->shouldBeCalledTimes(2)->willReturn(true, false);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            s3ClientWrapper: $s3ClientWrapper->reveal()
        );

        $s3Service->deleteFileChunks($mergeFileChunksOperation);
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

    public function testUploadFileChunkWithNoProvidedContentLengthWorks(): void
    {
        $uploadFileChunkOperation = $this->prophesize(UploadFileChunkOperationInterface::class);
        $uploadFileChunkOperation->getUploadBucket()->shouldBeCalledTimes(2)->willReturn('upload-bucket');
        $uploadFileChunkOperation->getUploadKey()->shouldBeCalledTimes(2)->willReturn('upload-key');
        $uploadFileChunkOperation->getContent()->shouldBeCalledOnce()->willReturn('some content');
        $uploadFileChunkOperation->getMimeType()->shouldBeCalledOnce()->willReturn('text/plain');
        $uploadFileChunkOperation->getContentLength()->shouldBeCalledOnce()->willReturn(null);

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(1234);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->putObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
            'Body' => 'some content',
            'ContentType' => 'text/plain',
        ]))->shouldBeCalledOnce();
        $s3Client->headObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $contentLength = $s3Service->uploadFileChunk($uploadFileChunkOperation->reveal());
        $this->assertSame(1234, $contentLength);
    }

    public function testUploadFileChunkWithProvidedCorrectContentLengthWorks(): void
    {
        $uploadFileChunkOperation = $this->prophesize(UploadFileChunkOperationInterface::class);
        $uploadFileChunkOperation->getUploadBucket()->shouldBeCalledTimes(2)->willReturn('upload-bucket');
        $uploadFileChunkOperation->getUploadKey()->shouldBeCalledTimes(2)->willReturn('upload-key');
        $uploadFileChunkOperation->getContent()->shouldBeCalledOnce()->willReturn('some content');
        $uploadFileChunkOperation->getMimeType()->shouldBeCalledOnce()->willReturn('text/plain');
        $uploadFileChunkOperation->getContentLength()->shouldBeCalledOnce()->willReturn(1234);

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(1234);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->putObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
            'Body' => 'some content',
            'ContentType' => 'text/plain',
        ]))->shouldBeCalledOnce();
        $s3Client->headObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $contentLength = $s3Service->uploadFileChunk($uploadFileChunkOperation->reveal());
        $this->assertSame(1234, $contentLength);
    }

    public function testUploadFileChunkWithProvidedIncorrectContentLengthThrowsException(): void
    {
        $uploadFileChunkOperation = $this->prophesize(UploadFileChunkOperationInterface::class);
        $uploadFileChunkOperation->getUploadBucket()->shouldBeCalledTimes(2)->willReturn('upload-bucket');
        $uploadFileChunkOperation->getUploadKey()->shouldBeCalledTimes(2)->willReturn('upload-key');
        $uploadFileChunkOperation->getContent()->shouldBeCalledOnce()->willReturn('some content');
        $uploadFileChunkOperation->getMimeType()->shouldBeCalledOnce()->willReturn('text/plain');
        $uploadFileChunkOperation->getContentLength()->shouldBeCalledOnce()->willReturn(4321);

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(1234);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->putObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
            'Body' => 'some content',
            'ContentType' => 'text/plain',
        ]))->shouldBeCalledOnce();
        $s3Client->headObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $exception = $this->prophesize(Client400BadContentException::class)->reveal();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory->createFromDetail(Argument::is('Inconsistent length values between provided content-length (4321) and actual content length (1234) detected.'))->shouldBeCalledOnce()->willReturn($exception);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal()
        );

        $this->expectException(Client400BadContentException::class);

        $s3Service->uploadFileChunk($uploadFileChunkOperation->reveal());
    }

    public function testUploadFile(): void
    {
        $uploadFileOperation = $this->prophesize(UploadFileOperationInterface::class);
        $uploadFileOperation->getContentLength()->shouldBeCalledOnce()->willReturn(null);
        $uploadFileOperation->getStorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $uploadFileOperation->getStorageKey()->shouldBeCalledOnce()->willReturn('storage-key');
        $uploadFileOperation->getUploadBucket()->shouldBeCalledTimes(2)->willReturn('upload-bucket');
        $uploadFileOperation->getUploadKey()->shouldBeCalledTimes(2)->willReturn('upload-key');
        $uploadFileOperation->getMimeType()->shouldBeCalledOnce()->willReturn('text/plain');
        $uploadFileOperation->getPreviousStorageKey()->shouldBeCalledOnce()->willReturn(null);
        $uploadFileOperation = $uploadFileOperation->reveal();

        $uploadFileChunkOperation = $this->prophesize(UploadFileChunkOperationInterface::class);
        $uploadFileChunkOperation->getUploadBucket()->shouldBeCalledTimes(2)->willReturn('upload-bucket');
        $uploadFileChunkOperation->getUploadKey()->shouldBeCalledTimes(2)->willReturn('upload-key');
        $uploadFileChunkOperation->getContent()->shouldBeCalledOnce()->willReturn('some content');
        $uploadFileChunkOperation->getMimeType()->shouldBeCalledOnce()->willReturn('text/plain');
        $uploadFileChunkOperation->getContentLength()->shouldBeCalledOnce()->willReturn(null);

        $copyObjectOutput = $this->prophesize(CopyObjectOutput::class)->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(1234);

        $objectExistsWaiter = $this->prophesize(ObjectExistsWaiter::class)->reveal();

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->putObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
            'Body' => 'some content',
            'ContentType' => 'text/plain',
        ]))->shouldBeCalledOnce();
        $s3Client->headObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());
        $s3Client->copyObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'CopySource' => 'upload-bucket/upload-key',
            'ContentType' => 'text/plain',
            'MetadataDirective' => 'REPLACE',
        ]))->shouldBeCalledOnce()->willReturn($copyObjectOutput);
        $s3Client->objectExists(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
        ]))->shouldBeCalledTimes(2)->willReturn($objectExistsWaiter);
        $s3Client->deleteObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
        ]))->shouldBeCalledOnce();

        $s3ClientWrapper = $this->prophesize(S3ClientWrapper::class);
        $s3ClientWrapper->getIsSuccessFromObjectExistsWaiter(Argument::is($objectExistsWaiter))->shouldBeCalledTimes(2)->willReturn(true, false);
        $s3ClientWrapper->resolveCopyObjectOutput(Argument::is($copyObjectOutput))->shouldBeCalledOnce();

        $uploadFileChunkOperationFactory = $this->prophesize(UploadFileChunkOperationFactory::class);
        $uploadFileChunkOperationFactory->createUploadFileChunkOperationFromUploadFileOperation(Argument::is($uploadFileOperation))
            ->shouldBeCalledOnce()->willReturn($uploadFileChunkOperation);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            fileChunkOperationFactory: $uploadFileChunkOperationFactory->reveal(),
            s3ClientWrapper: $s3ClientWrapper->reveal()
        );

        $contentLength = $s3Service->uploadFile($uploadFileOperation);
        $this->assertSame(1234, $contentLength);
    }

    public function testUploadFileDeletesPreviousFileIfItExists(): void
    {
        $uploadFileOperation = $this->prophesize(UploadFileOperationInterface::class);
        $uploadFileOperation->getContentLength()->shouldBeCalledOnce()->willReturn(null);
        $uploadFileOperation->getStorageBucket()->shouldBeCalledTimes(2)->willReturn('storage-bucket');
        $uploadFileOperation->getStorageKey()->shouldBeCalledTimes(2)->willReturn('storage-key');
        $uploadFileOperation->getUploadBucket()->shouldBeCalledTimes(2)->willReturn('upload-bucket');
        $uploadFileOperation->getUploadKey()->shouldBeCalledTimes(2)->willReturn('upload-key');
        $uploadFileOperation->getMimeType()->shouldBeCalledOnce()->willReturn('text/plain');
        $uploadFileOperation->getPreviousStorageKey()->shouldBeCalledOnce()->willReturn('storage-key.prev');
        $uploadFileOperation = $uploadFileOperation->reveal();

        $uploadFileChunkOperation = $this->prophesize(UploadFileChunkOperationInterface::class);
        $uploadFileChunkOperation->getUploadBucket()->shouldBeCalledTimes(2)->willReturn('upload-bucket');
        $uploadFileChunkOperation->getUploadKey()->shouldBeCalledTimes(2)->willReturn('upload-key');
        $uploadFileChunkOperation->getContent()->shouldBeCalledOnce()->willReturn('some content');
        $uploadFileChunkOperation->getMimeType()->shouldBeCalledOnce()->willReturn('text/plain');
        $uploadFileChunkOperation->getContentLength()->shouldBeCalledOnce()->willReturn(null);

        $copyObjectOutput = $this->prophesize(CopyObjectOutput::class)->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(1234);

        $objectExistsWaiter1 = $this->prophesize(ObjectExistsWaiter::class)->reveal();
        $objectExistsWaiter2 = $this->prophesize(ObjectExistsWaiter::class)->reveal();

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->putObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
            'Body' => 'some content',
            'ContentType' => 'text/plain',
        ]))->shouldBeCalledOnce();
        $s3Client->headObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());
        $s3Client->copyObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'CopySource' => 'upload-bucket/upload-key',
            'ContentType' => 'text/plain',
            'MetadataDirective' => 'REPLACE',
        ]))->shouldBeCalledOnce()->willReturn($copyObjectOutput);
        $s3Client->objectExists(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.prev',
        ]))->shouldBeCalledTimes(2)->willReturn($objectExistsWaiter1);
        $s3Client->deleteObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.prev',
        ]))->shouldBeCalledOnce();
        $s3Client->objectExists(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
        ]))->shouldBeCalledTimes(2)->willReturn($objectExistsWaiter2);
        $s3Client->deleteObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
        ]))->shouldBeCalledOnce();

        $s3ClientWrapper = $this->prophesize(S3ClientWrapper::class);
        $s3ClientWrapper->getIsSuccessFromObjectExistsWaiter(Argument::is($objectExistsWaiter1))->shouldBeCalledTimes(2)->willReturn(true, false);
        $s3ClientWrapper->getIsSuccessFromObjectExistsWaiter(Argument::is($objectExistsWaiter2))->shouldBeCalledTimes(2)->willReturn(true, false);
        $s3ClientWrapper->resolveCopyObjectOutput(Argument::is($copyObjectOutput))->shouldBeCalledOnce();

        $uploadFileChunkOperationFactory = $this->prophesize(UploadFileChunkOperationFactory::class);
        $uploadFileChunkOperationFactory->createUploadFileChunkOperationFromUploadFileOperation(Argument::is($uploadFileOperation))
            ->shouldBeCalledOnce()->willReturn($uploadFileChunkOperation);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            fileChunkOperationFactory: $uploadFileChunkOperationFactory->reveal(),
            s3ClientWrapper: $s3ClientWrapper->reveal()
        );

        $contentLength = $s3Service->uploadFile($uploadFileOperation);
        $this->assertSame(1234, $contentLength);
    }

    /**
     * A same-extension replace reuses the storage key, so the "previous" file is the one just written and must
     * not be deleted. The intermediate upload-bucket object is still cleaned up.
     */
    public function testUploadFileDoesNotDeletePreviousFileWhenItIsTheSameAsTheNewOne(): void
    {
        $uploadFileOperation = $this->prophesize(UploadFileOperationInterface::class);
        $uploadFileOperation->getContentLength()->shouldBeCalledOnce()->willReturn(null);
        $uploadFileOperation->getStorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $uploadFileOperation->getStorageKey()->shouldBeCalledTimes(2)->willReturn('storage-key');
        $uploadFileOperation->getUploadBucket()->shouldBeCalledTimes(2)->willReturn('upload-bucket');
        $uploadFileOperation->getUploadKey()->shouldBeCalledTimes(2)->willReturn('upload-key');
        $uploadFileOperation->getMimeType()->shouldBeCalledOnce()->willReturn('text/plain');
        $uploadFileOperation->getPreviousStorageKey()->shouldBeCalledOnce()->willReturn('storage-key');
        $uploadFileOperation = $uploadFileOperation->reveal();

        $uploadFileChunkOperation = $this->prophesize(UploadFileChunkOperationInterface::class);
        $uploadFileChunkOperation->getUploadBucket()->shouldBeCalledTimes(2)->willReturn('upload-bucket');
        $uploadFileChunkOperation->getUploadKey()->shouldBeCalledTimes(2)->willReturn('upload-key');
        $uploadFileChunkOperation->getContent()->shouldBeCalledOnce()->willReturn('some content');
        $uploadFileChunkOperation->getMimeType()->shouldBeCalledOnce()->willReturn('text/plain');
        $uploadFileChunkOperation->getContentLength()->shouldBeCalledOnce()->willReturn(null);

        $copyObjectOutput = $this->prophesize(CopyObjectOutput::class)->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(1234);

        $objectExistsWaiter = $this->prophesize(ObjectExistsWaiter::class)->reveal();

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->putObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
            'Body' => 'some content',
            'ContentType' => 'text/plain',
        ]))->shouldBeCalledOnce();
        $s3Client->headObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());
        $s3Client->copyObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'CopySource' => 'upload-bucket/upload-key',
            'ContentType' => 'text/plain',
            'MetadataDirective' => 'REPLACE',
        ]))->shouldBeCalledOnce()->willReturn($copyObjectOutput);
        // only the intermediate upload-bucket object is probed/deleted
        $s3Client->objectExists(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
        ]))->shouldBeCalledTimes(2)->willReturn($objectExistsWaiter);
        $s3Client->deleteObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
        ]))->shouldBeCalledOnce();
        $s3Client->objectExists(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
        ]))->shouldNotBeCalled();
        $s3Client->deleteObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
        ]))->shouldNotBeCalled();

        $s3ClientWrapper = $this->prophesize(S3ClientWrapper::class);
        $s3ClientWrapper->getIsSuccessFromObjectExistsWaiter(Argument::is($objectExistsWaiter))->shouldBeCalledTimes(2)->willReturn(true, false);
        $s3ClientWrapper->resolveCopyObjectOutput(Argument::is($copyObjectOutput))->shouldBeCalledOnce();

        $uploadFileChunkOperationFactory = $this->prophesize(UploadFileChunkOperationFactory::class);
        $uploadFileChunkOperationFactory->createUploadFileChunkOperationFromUploadFileOperation(Argument::is($uploadFileOperation))
            ->shouldBeCalledOnce()->willReturn($uploadFileChunkOperation);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            fileChunkOperationFactory: $uploadFileChunkOperationFactory->reveal(),
            s3ClientWrapper: $s3ClientWrapper->reveal()
        );

        $contentLength = $s3Service->uploadFile($uploadFileOperation);
        $this->assertSame(1234, $contentLength);
    }

    public function testUploadFileRethrowsExceptionDuringUpload(): void
    {
        $uploadFileOperation = $this->prophesize(UploadFileOperationInterface::class);
        $uploadFileOperation->getContentLength()->shouldBeCalledOnce()->willReturn(null);
        $uploadFileOperation->getStorageBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $uploadFileOperation->getStorageKey()->shouldBeCalledOnce()->willReturn('storage-key');
        $uploadFileOperation->getUploadBucket()->shouldBeCalledOnce()->willReturn('upload-bucket');
        $uploadFileOperation->getUploadKey()->shouldBeCalledOnce()->willReturn('upload-key');
        $uploadFileOperation->getMimeType()->shouldBeCalledOnce()->willReturn('text/plain');
        $uploadFileOperation->getPreviousStorageKey()->shouldNotBeCalled();
        $uploadFileOperation = $uploadFileOperation->reveal();

        $uploadFileChunkOperation = $this->prophesize(UploadFileChunkOperationInterface::class);
        $uploadFileChunkOperation->getUploadBucket()->shouldBeCalledTimes(2)->willReturn('upload-bucket');
        $uploadFileChunkOperation->getUploadKey()->shouldBeCalledTimes(2)->willReturn('upload-key');
        $uploadFileChunkOperation->getContent()->shouldBeCalledOnce()->willReturn('some content');
        $uploadFileChunkOperation->getMimeType()->shouldBeCalledOnce()->willReturn('text/plain');
        $uploadFileChunkOperation->getContentLength()->shouldBeCalledOnce()->willReturn(null);

        $copyObjectOutput = $this->prophesize(CopyObjectOutput::class)->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(1234);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->putObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
            'Body' => 'some content',
            'ContentType' => 'text/plain',
        ]))->shouldBeCalledOnce();
        $s3Client->headObject(Argument::is([
            'Bucket' => 'upload-bucket',
            'Key' => 'upload-key',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());
        $s3Client->copyObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'CopySource' => 'upload-bucket/upload-key',
            'ContentType' => 'text/plain',
            'MetadataDirective' => 'REPLACE',
        ]))->shouldBeCalledOnce()->willReturn($copyObjectOutput);

        $originalException = new Exception('some message');

        $finalException = $this->prophesize(Server500LogicErrorException::class)->reveal();

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory->createFromTemplate(Argument::is('Upload failed: some message'), Argument::is([]), Argument::is($originalException))->shouldBeCalledOnce()->willReturn($finalException);

        $s3ClientWrapper = $this->prophesize(S3ClientWrapper::class);
        $s3ClientWrapper->resolveCopyObjectOutput(Argument::is($copyObjectOutput))->shouldBeCalledOnce()->willThrow($originalException);

        $uploadFileChunkOperationFactory = $this->prophesize(UploadFileChunkOperationFactory::class);
        $uploadFileChunkOperationFactory->createUploadFileChunkOperationFromUploadFileOperation(Argument::is($uploadFileOperation))
            ->shouldBeCalledOnce()->willReturn($uploadFileChunkOperation);

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            fileChunkOperationFactory: $uploadFileChunkOperationFactory->reveal(),
            s3ClientWrapper: $s3ClientWrapper->reveal(),
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal()
        );

        $this->expectException(Server500LogicErrorException::class);

        $s3Service->uploadFile($uploadFileOperation);
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
            'Range' => 'bytes=0-4999999',
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
            'Range' => 'bytes=0-4320',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $resource = $s3Service->getFileRangeAsResource($fileOperation, 5000000);
        $this->assertSame('some content', $resource);
    }

    public function testGetFileRangeAsResourceReturnsEmptyResourceWithoutCallingS3WhenContentLengthIsZero(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(0);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->headObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());
        // S3 rejects byte-range requests against empty objects with 416
        $s3Client->getObject(Argument::any())->shouldNotBeCalled();

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $resource = $s3Service->getFileRangeAsResource($fileOperation, 5000000);
        $this->assertIsResource($resource);
        $this->assertSame('', \Safe\stream_get_contents($resource));
    }

    public function testGetFileRangeAsResourceRequestsSingleByteForOneByteFile(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledTimes(2)->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledTimes(2)->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(1);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->headObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $resultStream = $this->prophesize(ResultStream::class);
        $resultStream->getContentAsResource()->shouldBeCalledOnce()->willReturn('x');

        $getObjectOutput = $this->prophesize(GetObjectOutput::class);
        $getObjectOutput->getBody()->shouldBeCalledOnce()->willReturn($resultStream->reveal());

        $s3Client->getObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
            'Range' => 'bytes=0-0',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $resource = $s3Service->getFileRangeAsResource($fileOperation, 5000000);
        $this->assertSame('x', $resource);
    }

    public function testGetFileRangeAsResourceRequestsWholeFileWhenContentLengthEqualsMaxContentLength(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledTimes(2)->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledTimes(2)->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(1000);

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
            'Range' => 'bytes=0-999',
        ]))->shouldBeCalledOnce()->willReturn($getObjectOutput->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $resource = $s3Service->getFileRangeAsResource($fileOperation, 1000);
        $this->assertSame('some content', $resource);
    }

    public function testGetFileRangeAsResourceReturnsEmptyResourceWithoutCallingS3WhenMaxContentLengthIsZero(): void
    {
        $this->assertEmptyResourceWithoutS3CallsForMaxContentLength(0);
    }

    public function testGetFileRangeAsResourceReturnsEmptyResourceWithoutCallingS3WhenMaxContentLengthIsNegative(): void
    {
        $this->assertEmptyResourceWithoutS3CallsForMaxContentLength(-1);
    }

    private function assertEmptyResourceWithoutS3CallsForMaxContentLength(int $maxContentLength): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldNotBeCalled();
        $fileOperation->getKey()->shouldNotBeCalled();

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->headObject(Argument::any())->shouldNotBeCalled();
        $s3Client->getObject(Argument::any())->shouldNotBeCalled();

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $resource = $s3Service->getFileRangeAsResource($fileOperation->reveal(), $maxContentLength);
        $this->assertIsResource($resource);
        $this->assertSame('', \Safe\stream_get_contents($resource));
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

    public function testGetContentLength(): void
    {
        $fileOperation = $this->prophesize(FileOperationInterface::class);
        $fileOperation->getBucket()->shouldBeCalledOnce()->willReturn('storage-bucket');
        $fileOperation->getKey()->shouldBeCalledOnce()->willReturn('storage-key.ext');
        $fileOperation = $fileOperation->reveal();

        $headObjectOutput = $this->prophesize(HeadObjectOutput::class);
        $headObjectOutput->getContentLength()->shouldBeCalledOnce()->willReturn(1234);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->headObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key.ext',
        ]))->shouldBeCalledOnce()->willReturn($headObjectOutput->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal()
        );

        $contentLength = $s3Service->getContentLength($fileOperation);
        $this->assertSame(1234, $contentLength);
    }

    public function testGetContentLengthThrowsWhenContentLengthIsNull(): void
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

        $s3Service->getContentLength($fileOperation);
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
            'Range' => 'bytes=0-5242879',
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
            'Range' => 'bytes=0-5242879',
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

    /**
     * @param resource $resource
     */
    private function buildMultipartUploadFileOperation($resource, int $contentLength, ?string $previousStorageKey = null): UploadFileOperationInterface
    {
        $uploadFileOperation = $this->prophesize(UploadFileOperationInterface::class);
        $uploadFileOperation->getContentLength()->willReturn($contentLength);
        $uploadFileOperation->getStorageBucket()->willReturn('storage-bucket');
        $uploadFileOperation->getStorageKey()->willReturn('storage-key');
        $uploadFileOperation->getMimeType()->willReturn('text/plain');
        $uploadFileOperation->getContent()->willReturn($resource);
        $uploadFileOperation->getPreviousStorageKey()->willReturn($previousStorageKey);

        return $uploadFileOperation->reveal();
    }

    private function buildUploadPartOutput(string $etag): UploadPartOutput
    {
        $uploadPartOutput = $this->prophesize(UploadPartOutput::class);
        $uploadPartOutput->getETag()->willReturn($etag);

        return $uploadPartOutput->reveal();
    }

    private function buildCreateMultipartUploadOutput(?string $uploadId): CreateMultipartUploadOutput
    {
        $createMultipartUploadOutput = $this->prophesize(CreateMultipartUploadOutput::class);
        $createMultipartUploadOutput->getUploadId()->willReturn($uploadId);

        return $createMultipartUploadOutput->reveal();
    }

    public function testUploadFileUsesMultipartUploadAtOrAboveThreshold(): void
    {
        $content = str_repeat('a', 25);
        $resource = \Safe\fopen('php://memory', 'r+');
        \Safe\fwrite($resource, $content);
        \Safe\rewind($resource);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'ContentType' => 'text/plain',
        ]))->shouldBeCalledOnce()->willReturn($this->buildCreateMultipartUploadOutput('multipart-upload-id'));
        $s3Client->uploadPart(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'multipart-upload-id',
            'PartNumber' => 1,
            'Body' => substr($content, 0, 10),
        ]))->shouldBeCalledOnce()->willReturn($this->buildUploadPartOutput('etag-1'));
        $s3Client->uploadPart(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'multipart-upload-id',
            'PartNumber' => 2,
            'Body' => substr($content, 10, 10),
        ]))->shouldBeCalledOnce()->willReturn($this->buildUploadPartOutput('etag-2'));
        $s3Client->uploadPart(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'multipart-upload-id',
            'PartNumber' => 3,
            'Body' => substr($content, 20, 5),
        ]))->shouldBeCalledOnce()->willReturn($this->buildUploadPartOutput('etag-3'));
        $s3Client->completeMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'multipart-upload-id',
            'MultipartUpload' => [
                'Parts' => [
                    ['PartNumber' => 1, 'ETag' => 'etag-1'],
                    ['PartNumber' => 2, 'ETag' => 'etag-2'],
                    ['PartNumber' => 3, 'ETag' => 'etag-3'],
                ],
            ],
        ]))->shouldBeCalledOnce();
        // the intermediate upload bucket is skipped entirely on this path
        $s3Client->putObject(Argument::any())->shouldNotBeCalled();
        $s3Client->copyObject(Argument::any())->shouldNotBeCalled();

        $s3Service = $this->buildS3Service(s3Client: $s3Client->reveal(), multipartUploadThresholdInBytes: 20, multipartUploadPartSizeInBytes: 10);

        $this->assertSame(25, $s3Service->uploadFile($this->buildMultipartUploadFileOperation($resource, 25)));
    }

    public function testUploadFileViaMultipartUploadRewindsAlreadyConsumedStream(): void
    {
        $content = str_repeat('b', 15);
        $resource = \Safe\fopen('php://memory', 'r+');
        \Safe\fwrite($resource, $content);
        // mime type detection and hashing leave the stream at its end
        \Safe\rewind($resource);
        \Safe\stream_get_contents($resource);
        $this->assertTrue(feof($resource));

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::any())->shouldBeCalledOnce()->willReturn($this->buildCreateMultipartUploadOutput('multipart-upload-id'));
        $s3Client->uploadPart(Argument::withEntry('Body', substr($content, 0, 10)))->shouldBeCalledOnce()->willReturn($this->buildUploadPartOutput('etag-1'));
        $s3Client->uploadPart(Argument::withEntry('Body', substr($content, 10, 5)))->shouldBeCalledOnce()->willReturn($this->buildUploadPartOutput('etag-2'));
        $s3Client->completeMultipartUpload(Argument::any())->shouldBeCalledOnce();

        $s3Service = $this->buildS3Service(s3Client: $s3Client->reveal(), multipartUploadThresholdInBytes: 10, multipartUploadPartSizeInBytes: 10);

        $this->assertSame(15, $s3Service->uploadFile($this->buildMultipartUploadFileOperation($resource, 15)));
    }

    public function testUploadFileStaysOnSinglePutBelowThreshold(): void
    {
        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::any())->shouldNotBeCalled();

        $uploadFileOperation = $this->prophesize(UploadFileOperationInterface::class);
        $uploadFileOperation->getContentLength()->shouldBeCalledOnce()->willReturn(19);
        $uploadFileOperation->getStorageBucket()->willReturn('storage-bucket');
        $uploadFileOperation->getStorageKey()->willReturn('storage-key');
        $uploadFileOperation->getUploadBucket()->willReturn('upload-bucket');
        $uploadFileOperation->getUploadKey()->willReturn('upload-key');
        $uploadFileOperation->getMimeType()->willReturn('text/plain');
        $uploadFileOperation->getPreviousStorageKey()->willReturn(null);

        $fileChunkOperationFactory = $this->prophesize(UploadFileChunkOperationFactory::class);
        $fileChunkOperationFactory
            ->createUploadFileChunkOperationFromUploadFileOperation(Argument::any())
            ->willThrow(new Exception('single-PUT path reached'));

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            fileChunkOperationFactory: $fileChunkOperationFactory->reveal(),
            multipartUploadThresholdInBytes: 20
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('single-PUT path reached');
        $s3Service->uploadFile($uploadFileOperation->reveal());
    }

    public function testUploadFileViaMultipartUploadDeletesPreviousFile(): void
    {
        $resource = \Safe\fopen('php://memory', 'r+');
        \Safe\fwrite($resource, 'abcdefghij');
        \Safe\rewind($resource);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::any())->willReturn($this->buildCreateMultipartUploadOutput('multipart-upload-id'));
        $s3Client->uploadPart(Argument::any())->willReturn($this->buildUploadPartOutput('etag-1'));
        $s3Client->completeMultipartUpload(Argument::any())->shouldBeCalledOnce();
        $s3Client->deleteObject(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'previous-storage-key',
        ]))->shouldBeCalledOnce();
        $objectExistsWaiter = $this->prophesize(ObjectExistsWaiter::class)->reveal();
        $s3Client->objectExists(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'previous-storage-key',
        ]))->shouldBeCalledTimes(2)->willReturn($objectExistsWaiter);

        $s3ClientWrapper = $this->prophesize(S3ClientWrapper::class);
        $s3ClientWrapper->getIsSuccessFromObjectExistsWaiter($objectExistsWaiter)->willReturn(true, false);

        $s3Service = $this->buildS3Service(s3Client: $s3Client->reveal(), s3ClientWrapper: $s3ClientWrapper->reveal(), multipartUploadThresholdInBytes: 5, multipartUploadPartSizeInBytes: 1000);

        $this->assertSame(10, $s3Service->uploadFile($this->buildMultipartUploadFileOperation($resource, 10, 'previous-storage-key')));
    }

    public function testUploadFileViaMultipartUploadAbortsAndRethrowsOnFailure(): void
    {
        $resource = \Safe\fopen('php://memory', 'r+');
        \Safe\fwrite($resource, 'abcdefghij');
        \Safe\rewind($resource);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::any())->willReturn($this->buildCreateMultipartUploadOutput('multipart-upload-id'));
        $partException = new Exception('part upload exploded');
        $s3Client->uploadPart(Argument::any())->willThrow($partException);
        $s3Client->abortMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'multipart-upload-id',
        ]))->shouldBeCalledOnce();
        $s3Client->completeMultipartUpload(Argument::any())->shouldNotBeCalled();

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory
            ->createFromTemplate(Argument::containingString('part upload exploded'), Argument::is([]), Argument::is($partException))
            ->shouldBeCalledOnce()
            ->willReturn($this->prophesize(Server500LogicErrorException::class)->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal(),
            multipartUploadThresholdInBytes: 5,
            multipartUploadPartSizeInBytes: 1000
        );

        $this->expectException(Server500LogicErrorException::class);
        $s3Service->uploadFile($this->buildMultipartUploadFileOperation($resource, 10));
    }

    public function testUploadFileViaMultipartUploadRejectsMismatchedContentLength(): void
    {
        $resource = \Safe\fopen('php://memory', 'r+');
        \Safe\fwrite($resource, 'abcdefghij');
        \Safe\rewind($resource);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::any())->willReturn($this->buildCreateMultipartUploadOutput('multipart-upload-id'));
        $s3Client->uploadPart(Argument::any())->willReturn($this->buildUploadPartOutput('etag-1'));
        // nothing may be published at the storage key when the length does not add up
        $s3Client->completeMultipartUpload(Argument::any())->shouldNotBeCalled();
        $s3Client->abortMultipartUpload(Argument::is([
            'Bucket' => 'storage-bucket',
            'Key' => 'storage-key',
            'UploadId' => 'multipart-upload-id',
        ]))->shouldBeCalledOnce();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory
            ->createFromDetail(Argument::containingString('Inconsistent length values'), Argument::cetera())
            ->shouldBeCalledOnce()
            ->willReturn($this->prophesize(Client400BadContentException::class)->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal(),
            multipartUploadThresholdInBytes: 5,
            multipartUploadPartSizeInBytes: 1000
        );

        $this->expectException(Client400BadContentException::class);
        $s3Service->uploadFile($this->buildMultipartUploadFileOperation($resource, 99));
    }

    public function testUploadFileViaMultipartUploadKeepsOriginalExceptionWhenAbortFails(): void
    {
        $resource = \Safe\fopen('php://memory', 'r+');
        \Safe\fwrite($resource, 'abcdefghij');
        \Safe\rewind($resource);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::any())->willReturn($this->buildCreateMultipartUploadOutput('multipart-upload-id'));
        $partException = new Exception('part upload exploded');
        $s3Client->uploadPart(Argument::any())->willThrow($partException);
        $abortException = new Exception('abort exploded');
        $s3Client->abortMultipartUpload(Argument::any())->shouldBeCalledOnce()->willThrow($abortException);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->warning('Unable to abort multipart upload.', Argument::is([
            'bucket' => 'storage-bucket',
            'key' => 'storage-key',
            'uploadId' => 'multipart-upload-id',
            'exception' => $abortException,
        ]))->shouldBeCalledOnce();

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory
            ->createFromTemplate(Argument::containingString('part upload exploded'), Argument::is([]), Argument::is($partException))
            ->shouldBeCalledOnce()
            ->willReturn($this->prophesize(Server500LogicErrorException::class)->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal(),
            logger: $logger->reveal(),
            multipartUploadThresholdInBytes: 5,
            multipartUploadPartSizeInBytes: 1000
        );

        $this->expectException(Server500LogicErrorException::class);
        $s3Service->uploadFile($this->buildMultipartUploadFileOperation($resource, 10));
    }

    public function testUploadFileViaMultipartUploadKeepsClientErrorWhenLazyAbortFails(): void
    {
        $resource = \Safe\fopen('php://memory', 'r+');
        \Safe\fwrite($resource, 'abcdefghij');
        \Safe\rewind($resource);

        $abortException = new Exception('abort exploded');
        $abortOutput = $this->prophesize(AbortMultipartUploadOutput::class)->reveal();
        $s3ClientWrapper = $this->prophesize(S3ClientWrapper::class);
        $s3ClientWrapper->resolveAbortMultipartUploadOutput($abortOutput)->shouldBeCalledOnce()->willThrow($abortException);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::any())->willReturn($this->buildCreateMultipartUploadOutput('multipart-upload-id'));
        $s3Client->uploadPart(Argument::any())->willReturn($this->buildUploadPartOutput('etag-1'));
        $s3Client->completeMultipartUpload(Argument::any())->shouldNotBeCalled();
        $s3Client->abortMultipartUpload(Argument::any())->shouldBeCalledOnce()->willReturn($abortOutput);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->warning('Unable to abort multipart upload.', Argument::withEntry('exception', $abortException))->shouldBeCalledOnce();

        $client400BadContentExceptionFactory = $this->prophesize(Client400BadContentExceptionFactory::class);
        $client400BadContentExceptionFactory
            ->createFromDetail(Argument::containingString('Inconsistent length values'), Argument::cetera())
            ->shouldBeCalledOnce()
            ->willReturn($this->prophesize(Client400BadContentException::class)->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            s3ClientWrapper: $s3ClientWrapper->reveal(),
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory->reveal(),
            logger: $logger->reveal(),
            multipartUploadThresholdInBytes: 5,
            multipartUploadPartSizeInBytes: 1000
        );

        $this->expectException(Client400BadContentException::class);
        $s3Service->uploadFile($this->buildMultipartUploadFileOperation($resource, 99));
    }

    public function testUploadFileViaMultipartUploadFailsWhenNoUploadIdIsReturned(): void
    {
        $resource = \Safe\fopen('php://memory', 'r+');
        \Safe\rewind($resource);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::any())->willReturn($this->buildCreateMultipartUploadOutput(null));

        $server500LogicErrorExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $server500LogicErrorExceptionFactory
            ->createFromTemplate(Argument::containingString('Unable to create multipart upload.'), Argument::cetera())
            ->shouldBeCalledOnce()
            ->willReturn($this->prophesize(Server500LogicErrorException::class)->reveal());

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            server500LogicErrorExceptionFactory: $server500LogicErrorExceptionFactory->reveal(),
            multipartUploadThresholdInBytes: 5
        );

        $this->expectException(Server500LogicErrorException::class);
        $s3Service->uploadFile($this->buildMultipartUploadFileOperation($resource, 10));
    }

    /**
     * A fixed part size would exceed the backend's maximum part count for a large enough file, so the part size
     * has to grow with the file instead.
     */
    public function testMultipartUploadPartSizeScalesWithMaxChunkCount(): void
    {
        $content = str_repeat('a', 20);
        $resource = \Safe\fopen('php://memory', 'r+');
        \Safe\fwrite($resource, $content);
        \Safe\rewind($resource);

        $s3TechnicalLimits = $this->prophesize(S3TechnicalLimitsInterface::class);
        $s3TechnicalLimits->getMaxSinglePutSizeInBytes()->willReturn(5 * 1024 * 1024 * 1024);
        // only 2 parts allowed for a 20 byte file -> part size has to become 10, not the configured 1
        $s3TechnicalLimits->getMaxChunkCount()->willReturn(2);

        $s3Client = $this->prophesize(S3Client::class);
        $s3Client->createMultipartUpload(Argument::any())->willReturn($this->buildCreateMultipartUploadOutput('multipart-upload-id'));
        $s3Client->uploadPart(Argument::withEntry('PartNumber', 1))->shouldBeCalledOnce()->willReturn($this->buildUploadPartOutput('etag-1'));
        $s3Client->uploadPart(Argument::withEntry('PartNumber', 2))->shouldBeCalledOnce()->willReturn($this->buildUploadPartOutput('etag-2'));
        $s3Client->uploadPart(Argument::withEntry('PartNumber', 3))->shouldNotBeCalled();
        $s3Client->completeMultipartUpload(Argument::any())->shouldBeCalledOnce();

        $s3Service = $this->buildS3Service(
            s3Client: $s3Client->reveal(),
            s3TechnicalLimits: $s3TechnicalLimits->reveal(),
            multipartUploadThresholdInBytes: 5,
            multipartUploadPartSizeInBytes: 1
        );

        $this->assertSame(20, $s3Service->uploadFile($this->buildMultipartUploadFileOperation($resource, 20)));
    }
}
