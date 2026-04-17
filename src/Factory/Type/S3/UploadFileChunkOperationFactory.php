<?php

declare(strict_types=1);

namespace App\Factory\Type\S3;

use App\Contract\Request\PartialUploadRequestInterface;
use App\Contract\Request\ResumableUploadRequestInterface;
use App\Contract\S3\UploadFileChunkOperationInterface;
use App\Contract\S3\UploadFileOperationInterface;
use App\Contract\UploadInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\FileService;
use App\Type\S3\UploadFileChunkOperation;
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

    public function createUploadFileChunkOperationFromResumableUploadRequest(ResumableUploadRequestInterface $resumableUploadRequest, UuidInterface $uploadId): UploadFileChunkOperationInterface
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

    public function createUploadFileChunkOperationFromPartialUploadRequest(PartialUploadRequestInterface $partialUploadRequest, UploadInterface $upload): UploadFileChunkOperationInterface
    {
        return new UploadFileChunkOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $this->fileService->getUploadBucketKey($upload->getId(), $upload->getAlreadyUploadedChunks() + 1),
            $partialUploadRequest->getContent(),
            $partialUploadRequest->getContentLength(),
            'application/octet-stream'
        );
    }

    public function createUploadFileChunkOperationFromUploadFileOperation(UploadFileOperationInterface $uploadFileOperation): UploadFileChunkOperationInterface
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
