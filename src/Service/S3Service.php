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
use Throwable;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class S3Service
{
    /**
     * Size from which a file is written as a multipart upload rather than a single PUT. Well below the backend's
     * technical single-PUT ceiling ({@see S3TechnicalLimitsInterface::getMaxSinglePutSizeInBytes()}), which stays
     * the hard limit used for configuration validation: a single PUT of several GiB is one long, unresumable
     * request whose failure costs the whole transfer, so switching earlier is cheaper than riding the limit.
     */
    public const int MULTIPART_UPLOAD_THRESHOLD_IN_BYTES = 500 * 1024 * 1024;

    /**
     * Part size used when streaming a large file into a multipart upload. Scaled up when the file is big enough
     * that a fixed part size would exceed the backend's maximum part count.
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
            $this->s3Client->abortMultipartUpload([
                'Bucket' => $mergeFileChunksOperation->getStorageBucket(),
                'Key' => $mergeFileChunksOperation->getStorageKey(),
                'UploadId' => $multipartUploadId,
            ]);

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
     * Small files take the intermediate upload bucket route below; from
     * {@see MULTIPART_UPLOAD_THRESHOLD_IN_BYTES} upwards they are streamed straight into the storage bucket as a
     * multipart upload, since past the backend's single-PUT ceiling neither the PUT nor the `copyObject` this
     * route relies on could handle them at all.
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
     * Streams $uploadFileOperation's resource into the storage bucket part by part. The intermediate upload
     * bucket is skipped deliberately: a file this large could not be moved out of it afterwards, as a single
     * `copyObject` has the same size ceiling as a single PUT.
     *
     * One part is held in memory at a time, so peak usage is roughly the part size rather than the file size.
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

            // verified before completing, not after: a completed multipart upload is immediately live at the
            // final storage key, so finishing it first would leave a wrong-length object in place of the
            // previous one while telling the client the request failed
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
            $this->s3Client->abortMultipartUpload([
                'Bucket' => $storageBucket,
                'Key' => $storageKey,
                'UploadId' => $multipartUploadId,
            ]);

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
     * A fixed part size would run past the backend's maximum part count once the file is large enough, so the
     * part size grows with the file when it has to.
     */
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
     * Reads only the given inclusive byte range [$start, $end] from S3, via the `Range` request header, instead
     * of downloading the whole file.
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
        $contentLength = $this->getContentLength($fileOperation);
        if (0 === $contentLength) {
            // a byte-range request against an empty object has no satisfiable range (S3 rejects it with a 416),
            // and there is nothing to fetch either way - an empty resource is the correct result directly.
            return \Safe\fopen('php://memory', 'r');
        }
        $result = $this->s3Client->getObject([
            'Bucket' => $fileOperation->getBucket(),
            'Key' => $fileOperation->getKey(),
            'Range' => sprintf('bytes=0-%d', min($contentLength, $maxContentLength)),
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
     * Assumes that the first chunk a) exists and is b) sufficiently long to correctly determine the MimeType. This is
     * currently the case, as S3's minimum chunk length is 5MB - sufficient for MimeType detection.
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
