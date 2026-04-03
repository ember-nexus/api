<?php

declare(strict_types=1);

namespace App\Factory\Type\S3;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\FileService;
use App\Type\Request\ResumableUploadRequest;
use App\Type\S3\UploadFileChunkOperation;
use App\Type\S3\UploadFileOperation;
use EmberNexusBundle\Service\EmberNexusConfiguration;

class UploadFileChunkOperationFactory
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private FileService $fileService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function createUploadFileChunkOperationFromResumableUploadRequest(ResumableUploadRequest $resumableUploadRequest): UploadFileChunkOperation
    {
        if (false !== $resumableUploadRequest->isUploadComplete()) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("'UploadFileChunkOperation' requires 'ResumableUploadRequest' to contain partial content, i.e. be a chunked upload request.");
        }

        $elementId = $resumableUploadRequest->getElementId();
        $resource = $resumableUploadRequest->getContent();

        return new UploadFileChunkOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $this->fileService->getUploadBucketKey($elementId, 0),
            $resource,
            $resumableUploadRequest->getContentLength(),
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
