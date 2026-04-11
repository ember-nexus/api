<?php

declare(strict_types=1);

namespace App\Factory\Type\S3;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Service\ElementService;
use App\Service\FileService;
use App\Type\S3\FileOperation;
use App\Type\Upload;
use EmberNexusBundle\Service\EmberNexusConfiguration;

class FileOperationFactory
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private FileService $fileService,
        private ElementService $elementService,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    public function createFileOperationFromElement(NodeElementInterface|RelationElementInterface $element): FileOperation
    {
        $extension = $this->elementService->getFileNameExtension($element);
        $elementId = $element->getId();
        if (null === $elementId) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Expected elementId to be not null.');
        }

        return new FileOperation(
            $this->emberNexusConfiguration->getFileS3StorageBucket(),
            $this->fileService->getStorageBucketKey($elementId, $extension)
        );
    }

    public function createFileOperationFromUpload(Upload $upload, int $chunk): FileOperation
    {
        return new FileOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $this->fileService->getUploadBucketKey($upload->getId(), $chunk)
        );
    }
}
