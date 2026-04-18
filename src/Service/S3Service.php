<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\S3\FileOperationInterface;
use App\Contract\S3\MergeFileChunksOperationInterface;
use App\Contract\S3\UploadFileChunkOperationInterface;
use App\Contract\S3\UploadFileOperationInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Type\S3\FileOperation;
use App\Wrapper\S3ClientWrapper;
use AsyncAws\S3\Result\GetObjectOutput;
use AsyncAws\S3\S3Client;
use Throwable;

class S3Service
{
    public function __construct(
        private S3Client $s3Client,
        private UploadFileChunkOperationFactory $fileChunkOperationFactory,
        private S3ClientWrapper $s3ClientWrapper,
        private MimeTypeService $mimeTypeService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
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
     * todo: optimize upload for larger files using multipart-upload?, handled by https://github.com/ember-nexus/api/issues/452.
     */
    public function uploadFile(UploadFileOperationInterface $uploadFileOperation): void
    {
        // intermediate upload to "upload bucket"
        $uploadFileChunkOperation = $this->fileChunkOperationFactory->createUploadFileChunkOperationFromUploadFileOperation($uploadFileOperation);
        $this->uploadFileChunk($uploadFileChunkOperation);

        // transfer uploaded element to "storage bucket"
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
            $copyResult->resolve();
            $previousStorageKey = $uploadFileOperation->getPreviousStorageKey();
            if (null !== $previousStorageKey && $previousStorageKey !== $uploadFileOperation->getStorageKey()) {
                // delete previous uploaded element, if available
                $this->deleteFile(new FileOperation(
                    $uploadFileOperation->getStorageBucket(),
                    $previousStorageKey
                ));
            }

            // clean up upload bucket
            $this->deleteFile(new FileOperation(
                $uploadFileOperation->getUploadBucket(),
                $uploadFileOperation->getUploadKey()
            ));
        } catch (Throwable $e) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Upload failed: %s', $e->getMessage()), previous: $e);
        }
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
     * Returns the MimeType of a chunked merge file operation.
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
