<?php

declare(strict_types=1);

namespace App\Factory\Type\S3;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Contract\Request\ResumableUploadRequestInterface;
use App\Contract\S3\UploadFileOperationInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\FileService;
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
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    public function createUploadFileOperationFromResumableUploadRequest(ResumableUploadRequestInterface $resumableUploadRequest): UploadFileOperationInterface
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

    /**
     * @param resource $resource
     */
    public function createUploadFileOperationFromElementAndResource(NodeElementInterface|RelationElementInterface $element, mixed $resource): UploadFileOperationInterface
    {
        $elementId = $element->getId();
        if (null === $elementId) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Expected element.id to not be null.');
        }

        $extension = $this->elementService->getFileNameExtension($element);

        return new UploadFileOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $this->fileService->getUploadBucketKey($elementId, 0),
            $this->emberNexusConfiguration->getFileS3StorageBucket(),
            null,
            $this->fileService->getStorageBucketKey($elementId, $extension),
            $resource,
            null,
            $this->fileService->getMimeTypeFromResource($resource)
        );
    }
}
