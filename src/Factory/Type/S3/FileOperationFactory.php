<?php

declare(strict_types=1);

namespace App\Factory\Type\S3;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Contract\S3\FileOperationInterface;
use App\Contract\UploadInterface;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\ElementService;
use App\Service\FileService;
use App\Type\S3\FileOperation;
use EmberNexusBundle\Service\EmberNexusConfiguration;

class FileOperationFactory
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private FileService $fileService,
        private ElementService $elementService,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    public function createFileOperationFromUpload(UploadInterface $upload, int $chunk): FileOperationInterface
    {
        return new FileOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $this->fileService->getUploadBucketKey($upload->getId(), $chunk)
        );
    }

    public function createFileOperationFromElement(NodeElementInterface|RelationElementInterface $element): FileOperationInterface
    {
        $elementId = $element->getId();
        if (null === $elementId) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Expected elementId to be not null.');
        }
        $extension = $this->elementService->getFileNameExtension($element);

        return new FileOperation(
            $this->emberNexusConfiguration->getFileS3StorageBucket(),
            $this->fileService->getStorageBucketKey($elementId, $extension)
        );
    }
}
