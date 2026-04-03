<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Type\S3\UploadFileChunkOperation;
use App\Type\S3\UploadFileOperation;
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

    public function uploadFileChunk(UploadFileChunkOperation $uploadFileChunkOperation): void
    {
        $this->s3Client->putObject([
            'Bucket' => $uploadFileChunkOperation->getUploadBucket(),
            'Key' => $uploadFileChunkOperation->getUploadKey(),
            'Body' => $uploadFileChunkOperation->getContent(),
            'ContentType' => $uploadFileChunkOperation->getMimeType()
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
                throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Inconsistent length values between provided content-length (%d) and actual content length (%d) detected.", $providedContentLength, $uploadContentLength));
            }
        }
    }

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
            'ContentType'       => $uploadFileOperation->getMimeType(),
            'MetadataDirective' => 'REPLACE',
        ]);

        try {
            $copyResult->resolve();
            if ($uploadFileOperation->getPreviousStorageKey() !== null &&
                $uploadFileOperation->getPreviousStorageKey() !== $uploadFileOperation->getStorageKey()) {
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

}
