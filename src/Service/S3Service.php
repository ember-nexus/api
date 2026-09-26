<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\S3\FileOperationInterface;
use App\Contract\S3\MergeFileChunksOperationInterface;
use App\Contract\S3\S3TechnicalLimitsInterface;
use App\Contract\S3\UploadFileChunkOperationInterface;
use App\Contract\S3\UploadFileOperationInterface;
use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Type\S3\FileOperation;
use App\Wrapper\S3ClientWrapper;
use AsyncAws\S3\Result\GetObjectOutput;
use AsyncAws\S3\S3Client;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
class S3Service
{
    /**
     * Kept well below the backend's single PUT limit, as a failed single PUT has to be retried completely.
     */
    public const int MULTIPART_UPLOAD_THRESHOLD_IN_BYTES = 500 * 1024 * 1024;

    /**
     * Minimum part size, increased for large files to stay within the backend's maximum part count.
     */
    public const int MULTIPART_UPLOAD_PART_SIZE_IN_BYTES = 100 * 1024 * 1024;

    public function __construct(
        private S3Client $s3Client,
        private UploadFileChunkOperationFactory $uploadFileChunkOperationFactory,
        private S3ClientWrapper $s3ClientWrapper,
        private MimeTypeService $mimeTypeService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
        private S3TechnicalLimitsInterface $s3TechnicalLimits,
        private LoggerInterface $logger,
        S3TechnicalLimitsValidator $s3TechnicalLimitsValidator,
        private int $multipartUploadThresholdInBytes = self::MULTIPART_UPLOAD_THRESHOLD_IN_BYTES,
        private int $multipartUploadPartSizeInBytes = self::MULTIPART_UPLOAD_PART_SIZE_IN_BYTES,
    ) {
        $s3TechnicalLimitsValidator->validate($this->multipartUploadThresholdInBytes);
    }

    /**
     * @internal
     */
    public function createMultipartUploadFromMergeFileChunksOperation(MergeFileChunksOperationInterface $mergeFileChunksOperation): string
    {
        $createResult = $this->s3Client->createMultipartUpload([
            'Bucket' => $mergeFileChunksOperation->getStorageBucket(),
            'Key' => $mergeFileChunksOperation->getStorageKey(),
            'ContentType' => $this->getMimeTypeFromMergeFileChunksOperation($mergeFileChunksOperation),
        ]);

        $multipartUploadId = $createResult->getUploadId();

        if (null === $multipartUploadId) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Unable to create multipart upload.');
        }

        return $multipartUploadId;
    }

    /**
     * @internal
     *
     * @return array<int, array{PartNumber: int, ETag: string}>
     */
    public function createUploadPartsFromMergeFileChunksOperation(MergeFileChunksOperationInterface $mergeFileChunksOperation, string $multipartUploadId): array
    {
        $parts = [];
        foreach ($mergeFileChunksOperation->getUploadKeys() as $i => $uploadKey) {
            $copyResult = $this->s3Client->uploadPartCopy([
                'Bucket' => $mergeFileChunksOperation->getStorageBucket(),
                'Key' => $mergeFileChunksOperation->getStorageKey(),
                'UploadId' => $multipartUploadId,
                'PartNumber' => $i + 1,
                'CopySource' => sprintf('%s/%s', $mergeFileChunksOperation->getUploadBucket(), $uploadKey),
            ]);

            $copyPartResult = $copyResult->getCopyPartResult();
            if (null === $copyPartResult) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Unable to read copy part result.');
            }

            $etag = $copyPartResult->getETag();
            if (null === $etag) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Unable to read etag of copy part result.');
            }

            $parts[] = [
                'PartNumber' => $i + 1,
                'ETag' => $etag,
            ];
        }

        return $parts;
    }

    public function mergeFileChunks(MergeFileChunksOperationInterface $mergeFileChunksOperation): int
    {
        $multipartUploadId = $this->createMultipartUploadFromMergeFileChunksOperation($mergeFileChunksOperation);
        try {
            $parts = $this->createUploadPartsFromMergeFileChunksOperation($mergeFileChunksOperation, $multipartUploadId);
            $this->s3Client->completeMultipartUpload([
                'Bucket' => $mergeFileChunksOperation->getStorageBucket(),
                'Key' => $mergeFileChunksOperation->getStorageKey(),
                'UploadId' => $multipartUploadId,
                'MultipartUpload' => [
                    'Parts' => $parts,
                ],
            ]);
        } catch (Throwable $e) {
            $this->tryAbortMultipartUpload($mergeFileChunksOperation->getStorageBucket(), $mergeFileChunksOperation->getStorageKey(), $multipartUploadId);

            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf("Caught exception '%s' during multipart upload.", $e->getMessage()), previous: $e);
        }

        $mergedContentLength = $this->getContentLength(new FileOperation(
            $mergeFileChunksOperation->getStorageBucket(),
            $mergeFileChunksOperation->getStorageKey()
        ));

        $previousStorageKey = $mergeFileChunksOperation->getPreviousStorageKey();
        if (null !== $previousStorageKey && $previousStorageKey !== $mergeFileChunksOperation->getStorageKey()) {
            $this->deleteFile(new FileOperation(
                $mergeFileChunksOperation->getStorageBucket(),
                $previousStorageKey
            ));
        }

        return $mergedContentLength;
    }

    public function deleteFileChunks(MergeFileChunksOperationInterface $mergeFileChunksOperation): void
    {
        foreach ($mergeFileChunksOperation->getUploadKeys() as $uploadKey) {
            $this->deleteFile(new FileOperation(
                $mergeFileChunksOperation->getUploadBucket(),
                $uploadKey
            ));
        }
    }

    /**
     * @return int length of the uploaded chunk
     */
    public function uploadFileChunk(UploadFileChunkOperationInterface $uploadFileChunkOperation): int
    {
        $this->s3Client->putObject([
            'Bucket' => $uploadFileChunkOperation->getUploadBucket(),
            'Key' => $uploadFileChunkOperation->getUploadKey(),
            'Body' => $uploadFileChunkOperation->getContent(),
            'ContentType' => $uploadFileChunkOperation->getMimeType(),
        ]);

        $uploadContentLength = $this->getContentLength(new FileOperation(
            $uploadFileChunkOperation->getUploadBucket(),
            $uploadFileChunkOperation->getUploadKey()
        ));

        $providedContentLength = $uploadFileChunkOperation->getContentLength();
        if (null !== $providedContentLength) {
            if ($providedContentLength !== $uploadContentLength) {
                throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Inconsistent length values between provided content-length (%d) and actual content length (%d) detected.', $providedContentLength, $uploadContentLength));
            }
        }

        return $uploadContentLength;
    }

    /**
     * Files from {@see MULTIPART_UPLOAD_THRESHOLD_IN_BYTES} upwards are streamed directly into the storage bucket as
     * a multipart upload, smaller ones are copied over from the upload bucket.
     */
    public function uploadFile(UploadFileOperationInterface $uploadFileOperation): int
    {
        $contentLength = $uploadFileOperation->getContentLength();
        if (null !== $contentLength && $contentLength >= $this->multipartUploadThresholdInBytes) {
            return $this->uploadFileViaMultipartUpload($uploadFileOperation, $contentLength);
        }

        $uploadFileChunkOperation = $this->uploadFileChunkOperationFactory->createUploadFileChunkOperationFromUploadFileOperation($uploadFileOperation);
        $contentLength = $this->uploadFileChunk($uploadFileChunkOperation);

        $copyResult = $this->s3Client->copyObject([
            'Bucket' => $uploadFileOperation->getStorageBucket(),
            'Key' => $uploadFileOperation->getStorageKey(),
            'CopySource' => sprintf(
                '%s/%s',
                $uploadFileOperation->getUploadBucket(),
                $uploadFileOperation->getUploadKey()
            ),
            'ContentType' => $uploadFileOperation->getMimeType(),
            'MetadataDirective' => 'REPLACE',
        ]);

        try {
            $this->s3ClientWrapper->resolveCopyObjectOutput($copyResult);
            $previousStorageKey = $uploadFileOperation->getPreviousStorageKey();
            if (null !== $previousStorageKey && $previousStorageKey !== $uploadFileOperation->getStorageKey()) {
                $this->deleteFile(new FileOperation(
                    $uploadFileOperation->getStorageBucket(),
                    $previousStorageKey
                ));
            }

            $this->deleteFile(new FileOperation(
                $uploadFileOperation->getUploadBucket(),
                $uploadFileOperation->getUploadKey()
            ));
        } catch (Throwable $e) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Upload failed: %s', $e->getMessage()), previous: $e);
        }

        return $contentLength;
    }

    /**
     * Skips the upload bucket, as `copyObject` has the same size limit as a single PUT. Only one part is held in
     * memory at a time.
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function uploadFileViaMultipartUpload(UploadFileOperationInterface $uploadFileOperation, int $contentLength): int
    {
        $storageBucket = $uploadFileOperation->getStorageBucket();
        $storageKey = $uploadFileOperation->getStorageKey();
        $partSizeInBytes = $this->getMultipartUploadPartSizeInBytes($contentLength);

        $multipartUploadId = $this->s3Client->createMultipartUpload([
            'Bucket' => $storageBucket,
            'Key' => $storageKey,
            'ContentType' => $uploadFileOperation->getMimeType(),
        ])->getUploadId();

        if (null === $multipartUploadId) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Unable to create multipart upload.');
        }

        $uploadedContentLength = 0;
        try {
            $parts = [];
            $resource = $uploadFileOperation->getContent();
            // mime type detection and hashing have already consumed part of the stream
            \Safe\rewind($resource);
            while (!feof($resource)) {
                $body = \Safe\stream_get_contents($resource, $partSizeInBytes);
                if ('' === $body) {
                    break;
                }

                $partNumber = count($parts) + 1;
                $etag = $this->s3Client->uploadPart([
                    'Bucket' => $storageBucket,
                    'Key' => $storageKey,
                    'UploadId' => $multipartUploadId,
                    'PartNumber' => $partNumber,
                    'Body' => $body,
                ])->getETag();

                if (null === $etag) {
                    throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Unable to read etag of uploaded part %d.', $partNumber));
                }

                $parts[] = [
                    'PartNumber' => $partNumber,
                    'ETag' => $etag,
                ];
                $uploadedContentLength += strlen($body);
            }

            // verified before completing, as a completed multipart upload would already replace the previous file
            if ($uploadedContentLength !== $contentLength) {
                throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Inconsistent length values between provided content-length (%d) and actual content length (%d) detected.', $contentLength, $uploadedContentLength));
            }

            $this->s3Client->completeMultipartUpload([
                'Bucket' => $storageBucket,
                'Key' => $storageKey,
                'UploadId' => $multipartUploadId,
                'MultipartUpload' => [
                    'Parts' => $parts,
                ],
            ]);
        } catch (Throwable $e) {
            $this->tryAbortMultipartUpload($storageBucket, $storageKey, $multipartUploadId);

            if ($e instanceof Client400BadContentException) {
                throw $e;
            }

            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf("Caught exception '%s' during multipart upload.", $e->getMessage()), previous: $e);
        }

        $previousStorageKey = $uploadFileOperation->getPreviousStorageKey();
        if (null !== $previousStorageKey && $previousStorageKey !== $storageKey) {
            $this->deleteFile(new FileOperation($storageBucket, $previousStorageKey));
        }

        return $uploadedContentLength;
    }

    /**
     * Failures are only logged, so that they do not replace the original exception.
     */
    private function tryAbortMultipartUpload(string $bucket, string $key, string $multipartUploadId): void
    {
        try {
            // AsyncAws requests are lazy; resolve() forces a failure to be thrown here instead of on destruction
            $this->s3ClientWrapper->resolveAbortMultipartUploadOutput($this->s3Client->abortMultipartUpload([
                'Bucket' => $bucket,
                'Key' => $key,
                'UploadId' => $multipartUploadId,
            ]));
        } catch (Throwable $e) {
            $this->logger->warning('Unable to abort multipart upload.', [
                'bucket' => $bucket,
                'key' => $key,
                'uploadId' => $multipartUploadId,
                'exception' => $e,
            ]);
        }
    }

    private function getMultipartUploadPartSizeInBytes(int $contentLength): int
    {
        $partSizeRequiredByMaxChunkCount = (int) ceil($contentLength / $this->s3TechnicalLimits->getMaxChunkCount());

        return max($this->multipartUploadPartSizeInBytes, $partSizeRequiredByMaxChunkCount);
    }

    public function deleteFile(FileOperationInterface $fileOperation): void
    {
        $objectConfig = [
            'Bucket' => $fileOperation->getBucket(),
            'Key' => $fileOperation->getKey(),
        ];

        $objectExistsWaiter = $this->s3Client->objectExists($objectConfig);
        if (!$this->s3ClientWrapper->getIsSuccessFromObjectExistsWaiter($objectExistsWaiter)) {
            return;
        }

        $this->s3Client->deleteObject($objectConfig);
        $objectExistsWaiter = $this->s3Client->objectExists($objectConfig);
        if ($this->s3ClientWrapper->getIsSuccessFromObjectExistsWaiter($objectExistsWaiter)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Unable to delete file.');
        }
    }

    public function existsFile(FileOperationInterface $fileOperation): bool
    {
        $objectConfig = [
            'Bucket' => $fileOperation->getBucket(),
            'Key' => $fileOperation->getKey(),
        ];
        $objectExistsWaiter = $this->s3Client->objectExists($objectConfig);

        return $this->s3ClientWrapper->getIsSuccessFromObjectExistsWaiter($objectExistsWaiter);
    }

    public function getFile(FileOperationInterface $fileOperation): GetObjectOutput
    {
        $objectConfig = [
            'Bucket' => $fileOperation->getBucket(),
            'Key' => $fileOperation->getKey(),
        ];

        return $this->s3Client->getObject($objectConfig);
    }

    /**
     * Reads the inclusive byte range [$start, $end].
     */
    public function getFileByteRange(FileOperationInterface $fileOperation, int $start, int $end): GetObjectOutput
    {
        return $this->s3Client->getObject([
            'Bucket' => $fileOperation->getBucket(),
            'Key' => $fileOperation->getKey(),
            'Range' => sprintf('bytes=%d-%d', $start, $end),
        ]);
    }

    /**
     * @return resource
     */
    public function getFileAsResource(FileOperationInterface $fileOperation)
    {
        $objectConfig = [
            'Bucket' => $fileOperation->getBucket(),
            'Key' => $fileOperation->getKey(),
        ];

        $object = $this->s3Client->getObject($objectConfig);

        return $object->getBody()->getContentAsResource();
    }

    /**
     * @return resource
     */
    public function getFileRangeAsResource(FileOperationInterface $fileOperation, int $maxContentLength)
    {
        if ($maxContentLength <= 0) {
            return \Safe\fopen('php://memory', 'r');
        }
        $contentLength = $this->getContentLength($fileOperation);
        $lengthToRead = min($contentLength, $maxContentLength);
        if ($lengthToRead <= 0) {
            // S3 rejects range requests against empty objects with a 416
            return \Safe\fopen('php://memory', 'r');
        }
        $result = $this->s3Client->getObject([
            'Bucket' => $fileOperation->getBucket(),
            'Key' => $fileOperation->getKey(),
            'Range' => sprintf('bytes=0-%d', $lengthToRead - 1),
        ]);

        return $result->getBody()->getContentAsResource();
    }

    public function getContentLength(FileOperationInterface $fileOperation): int
    {
        $headResult = $this->s3Client->headObject([
            'Bucket' => $fileOperation->getBucket(),
            'Key' => $fileOperation->getKey(),
        ]);
        $contentLength = $headResult->getContentLength();

        if (null === $contentLength) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Unable to read content length of file.');
        }

        return $contentLength;
    }

    public function getEtag(FileOperationInterface $fileOperation): string
    {
        $headResult = $this->s3Client->headObject([
            'Bucket' => $fileOperation->getBucket(),
            'Key' => $fileOperation->getKey(),
        ]);

        $etag = $headResult->getETag();

        if (null === $etag) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Unable to retrieve file.');
        }

        return $etag;
    }

    public function getMimeTypeFromFile(FileOperationInterface $fileOperation): string
    {
        $resource = $this->getFileRangeAsResource($fileOperation, MimeTypeService::NECESSARY_BYTES_FOR_MIME_TYPE_DETECTION);

        return $this->mimeTypeService->getMimeTypeFromResource($resource);
    }

    /**
     * Only the first chunk is used for detection, which is sufficient due to the minimum chunk size of 5 MiB.
     */
    public function getMimeTypeFromMergeFileChunksOperation(MergeFileChunksOperationInterface $mergeFileChunksOperation): string
    {
        $uploadKeys = $mergeFileChunksOperation->getUploadKeys();
        if (0 === count($uploadKeys)) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Creating a single file from multiple uploaded chunks requires at least one chunk to be present, got none.');
        }

        return $this->getMimeTypeFromFile(new FileOperation(
            $mergeFileChunksOperation->getUploadBucket(),
            $uploadKeys[0]
        ));
    }
}
