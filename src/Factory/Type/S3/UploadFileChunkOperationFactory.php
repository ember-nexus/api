<?php

declare(strict_types=1);

namespace App\Factory\Type\S3;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\FileService;
use App\Type\Request\PartialUploadRequest;
use App\Type\Request\ResumableUploadRequest;
use App\Type\S3\UploadFileChunkOperation;
use App\Type\S3\UploadFileOperation;
use App\Type\Upload;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Ramsey\Uuid\UuidInterface;

class UploadFileChunkOperationFactory
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private FileService $fileService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function createUploadFileChunkOperationFromResumableUploadRequest(ResumableUploadRequest $resumableUploadRequest, UuidInterface $uploadId): UploadFileChunkOperation
    {
        if (true === $resumableUploadRequest->isUploadComplete()) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("'UploadFileChunkOperation' requires 'ResumableUploadRequest' to contain partial content, i.e. be a chunked upload request.");
        }

        return new UploadFileChunkOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $this->fileService->getUploadBucketKey($uploadId, 1),
            $resumableUploadRequest->getContent(),
            $resumableUploadRequest->getContentLength(),
            'application/octet-stream'
        );
    }

    public function createUploadFileChunkOperationFromPartialUploadRequest(PartialUploadRequest $partialUploadRequest, Upload $upload): UploadFileChunkOperation
    {
        if (true !== $partialUploadRequest->isUploadComplete()) {
            // todo: check resource stream for at least 5 mb of length
        }

        $resource = $partialUploadRequest->getContent();

        return new UploadFileChunkOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $this->fileService->getUploadBucketKey($upload->getId(), $upload->getAlreadyUploadedChunks() + 1),
            $resource,
            $partialUploadRequest->getContentLength(),
            'application/octet-stream'
        );
    }

    public function createUploadFileChunkOperationFromUploadFileOperation(UploadFileOperation $uploadFileOperation): UploadFileChunkOperation
    {
        return new UploadFileChunkOperation(
            $uploadFileOperation->getUploadBucket(),
            $uploadFileOperation->getUploadKey(),
            $uploadFileOperation->getContent(),
            $uploadFileOperation->getContentLength(),
            $uploadFileOperation->getMimeType()
        );
    }
}
