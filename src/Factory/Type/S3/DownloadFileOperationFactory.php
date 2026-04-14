<?php

declare(strict_types=1);

namespace App\Factory\Type\S3;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Service\ElementService;
use App\Service\FileService;
use App\Type\S3\DownloadFileOperation;
use EmberNexusBundle\Service\EmberNexusConfiguration;

class DownloadFileOperationFactory
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private FileService $fileService,
        private ElementService $elementService,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    public function createDownloadFileOperationFromElement(
        NodeElementInterface|RelationElementInterface $element,
        string $backupName,
        int $levels,
    ): DownloadFileOperation {
        $elementId = $element->getId();
        if (null === $elementId) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Expected elementId to be not null.');
        }

        $extension = $this->elementService->getFileNameExtension($element);

        return new DownloadFileOperation(
            $this->emberNexusConfiguration->getFileS3StorageBucket(),
            $this->fileService->getStorageBucketKey($elementId, $extension),
            sprintf(
                '%s/file/%s.%s',
                $backupName,
                $this->fileService->uuidToNestedFolderStructure($elementId, $levels),
                $extension
            )
        );
    }
}
