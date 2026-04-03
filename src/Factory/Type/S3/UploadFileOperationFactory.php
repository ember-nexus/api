<?php

declare(strict_types=1);

namespace App\Factory\Type\S3;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\FileService;
use App\Type\Request\ResumableUploadRequest;
use App\Type\S3\UploadFileOperation;
use EmberNexusBundle\Service\EmberNexusConfiguration;

class UploadFileOperationFactory
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private ElementManager $elementManager,
        private ElementService $elementService,
        private FileService $fileService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function createUploadFileOperationFromResumableUploadRequest(ResumableUploadRequest $resumableUploadRequest): UploadFileOperation
    {
        if (false === $resumableUploadRequest->isUploadComplete()) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("'UploadFileOperation' requires 'ResumableUploadRequest' to contain the whole content, i.e. be a non-chunked upload request.");
        }

        $elementId = $resumableUploadRequest->getElementId();
        $element = $this->elementManager->getElementOrFail($elementId);

        $previousStorageKey = null;
        if ($element->hasProperty('file')) {
            $previousExtension = $this->elementService->getFileNameExtension($element);
            $previousStorageKey = $this->fileService->getStorageBucketKey($elementId, $previousExtension);
        }

        $resource = $resumableUploadRequest->getContent();

        return new UploadFileOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $this->fileService->getUploadBucketKey($elementId, 0),
            $this->emberNexusConfiguration->getFileS3StorageBucket(),
            $previousStorageKey,
            $this->fileService->getStorageBucketKey($elementId, $resumableUploadRequest->getExtension()),
            $resource,
            $resumableUploadRequest->getContentLength(),
            $this->fileService->getMimeTypeFromResource($resource)
        );
    }
}
