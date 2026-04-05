<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Type\S3\FileOperation;
use App\Type\S3\UploadFileChunkOperation;
use App\Type\S3\UploadFileOperation;
use AsyncAws\S3\Result\GetObjectOutput;
use AsyncAws\S3\S3Client;
use Throwable;

class S3Service
{
    public function __construct(
        private S3Client $s3Client,
        private UploadFileChunkOperationFactory $fileChunkOperationFactory,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    /**
     * @return int length of the uploaded chunk
     *
     * @throws \App\Exception\Client400BadContentException
     * @throws \App\Exception\Server500LogicErrorException
     */
    public function uploadFileChunk(UploadFileChunkOperation $uploadFileChunkOperation): int
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
            throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to read content length of uploaded data.');
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
    public function uploadFile(UploadFileOperation $uploadFileOperation): void
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
            if (null !== $uploadFileOperation->getPreviousStorageKey()
                && $uploadFileOperation->getPreviousStorageKey() !== $uploadFileOperation->getStorageKey()) {
                // delete previous uploaded element, if available
                $objectConfig = [
                    'Bucket' => $uploadFileOperation->getStorageBucket(),
                    'Key' => $uploadFileOperation->getPreviousStorageKey(),
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
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Upload failed: %s', $e->getMessage()), previous: $e);
        }
    }

    public function deleteFile(FileOperation $fileOperation): void
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
            throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to delete file.');
        }
    }

    public function existsFile(FileOperation $fileOperation): bool
    {
        $objectConfig = [
            'Bucket' => $fileOperation->getBucket(),
            'Key' => $fileOperation->getKey(),
        ];
        $status = $this->s3Client->objectExists($objectConfig);

        return $status->isSuccess();
    }

    public function getFile(FileOperation $fileOperation): GetObjectOutput
    {
        $objectConfig = [
            'Bucket' => $fileOperation->getBucket(),
            'Key' => $fileOperation->getKey(),
        ];

        return $this->s3Client->getObject($objectConfig);
    }
}
