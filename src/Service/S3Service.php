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
use finfo;
use Throwable;

class S3Service
{
    public function __construct(
        private S3Client $s3Client,
        private UploadFileChunkOperationFactory $fileChunkOperationFactory,
        private S3ClientWrapper $s3ClientWrapper,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    public function getMimeTypeFromFile(FileOperationInterface $fileOperation): string
    {
        $headResult = $this->s3Client->headObject([
            'Bucket' => $fileOperation->getBucket(),
            'Key' => $fileOperation->getKey(),
        ]);
        $contentLength = $headResult->getContentLength();

        if (null === $contentLength) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Unable to read content length of file.');
        }

        $result = $this->s3Client->getObject([
            'Bucket' => $fileOperation->getBucket(),
            'Key' => $fileOperation->getKey(),
            'Range' => sprintf('bytes=0-%d', min($contentLength, 5 * 1024 * 1024)), // get first 5 MiB of file for mime type detection
        ]);

        $resource = $result->getBody()->getContentAsString();

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($resource);

        return false === $mimeType ? FileService::DEFAULT_MIME_TYPE : $mimeType;
    }

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

    public function mergeFileChunks(MergeFileChunksOperationInterface $mergeFileChunksOperation): int
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

        $parts = [];

        try {
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

                $parts[] = [
                    'PartNumber' => $i + 1,
                    'ETag' => $copyPartResult->getETag(),
                ];
            }

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

        $headResult = $this->s3Client->headObject([
            'Bucket' => $mergeFileChunksOperation->getStorageBucket(),
            'Key' => $mergeFileChunksOperation->getStorageKey(),
        ]);

        $mergedContentLength = $headResult->getContentLength();

        if (null === $mergedContentLength) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Unable to read content length of merged data.');
        }

        $previousStorageKey = $mergeFileChunksOperation->getPreviousStorageKey();
        if (null !== $previousStorageKey && $previousStorageKey !== $mergeFileChunksOperation->getStorageKey()) {
            // delete previous uploaded element, if available
            $objectConfig = [
                'Bucket' => $mergeFileChunksOperation->getStorageBucket(),
                'Key' => $previousStorageKey,
            ];
            $status = $this->s3Client->objectExists($objectConfig);

            if ($status->isSuccess()) {
                $this->s3Client->deleteObject($objectConfig);
            }
        }

        return $mergedContentLength;
    }

    public function deleteFileChunks(MergeFileChunksOperationInterface $mergeFileChunksOperation): void
    {
        foreach ($mergeFileChunksOperation->getUploadKeys() as $uploadKey) {
            $objectConfig = [
                'Bucket' => $mergeFileChunksOperation->getUploadBucket(),
                'Key' => $uploadKey,
            ];
            $status = $this->s3Client->objectExists($objectConfig);

            if ($status->isSuccess()) {
                $this->s3Client->deleteObject($objectConfig);
            }
        }
    }

    /**
     * @return int length of the uploaded chunk
     *
     * @throws \App\Exception\Client400BadContentException
     * @throws \App\Exception\Server500LogicErrorException
     */
    public function uploadFileChunk(UploadFileChunkOperationInterface $uploadFileChunkOperation): int
    {
        $this->s3Client->putObject([
            'Bucket' => $uploadFileChunkOperation->getUploadBucket(),
            'Key' => $uploadFileChunkOperation->getUploadKey(),
            'Body' => $uploadFileChunkOperation->getContent(),
            'ContentType' => $uploadFileChunkOperation->getMimeType(),
        ]);

        $headResult = $this->s3Client->headObject([
            'Bucket' => $uploadFileChunkOperation->getUploadBucket(),
            'Key' => $uploadFileChunkOperation->getUploadKey(),
        ]);

        $uploadContentLength = $headResult->getContentLength();

        if (null === $uploadContentLength) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Unable to read content length of uploaded data.');
        }

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
                $objectConfig = [
                    'Bucket' => $uploadFileOperation->getStorageBucket(),
                    'Key' => $previousStorageKey,
                ];
                $status = $this->s3Client->objectExists($objectConfig);

                if ($status->isSuccess()) {
                    $this->s3Client->deleteObject($objectConfig);
                }
            }

            // clean up upload bucket

            $deleteResult = $this->s3Client->deleteObject([
                'Bucket' => $uploadFileOperation->getUploadBucket(),
                'Key' => $uploadFileOperation->getUploadKey(),
            ]);
            $deleteResult->resolve();
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
        $status = $this->s3Client->objectExists($objectConfig);

        if ($status->isSuccess()) {
            $this->s3Client->deleteObject($objectConfig);
        }

        $status = $this->s3Client->objectExists($objectConfig);
        if ($status->isSuccess()) {
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
    public function getFileAsResource(FileOperationInterface $fileOperation): mixed
    {
        $objectConfig = [
            'Bucket' => $fileOperation->getBucket(),
            'Key' => $fileOperation->getKey(),
        ];

        $object = $this->s3Client->getObject($objectConfig);

        return $object->getBody()->getContentAsResource();
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
}
