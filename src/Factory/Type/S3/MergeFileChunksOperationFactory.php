<?php

declare(strict_types=1);

namespace App\Factory\Type\S3;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\FileService;
use App\Type\S3\MergeFileChunksOperation;
use App\Type\Upload;
use EmberNexusBundle\Service\EmberNexusConfiguration;

class MergeFileChunksOperationFactory
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private ElementManager $elementManager,
        private ElementService $elementService,
        private FileService $fileService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function createMergeFileOperationFromUpload(Upload $upload): MergeFileChunksOperation
    {
        if (false === $upload->isUploadComplete()) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("'MergeFileChunksOperation' requires 'Upload' to be complete.");
        }

        $uploadKeys = [];
        for ($i = 0; $i < $upload->getAlreadyUploadedChunks(); ++$i) {
            $uploadKeys[] = $this->fileService->getUploadBucketKey($upload->getId(), $i + 1);
        }

        $element = $this->elementManager->getElementOrFail($upload->getUploadTarget());

        $previousStorageKey = null;
        if ($element->hasProperty('file')) {
            $previousExtension = $this->elementService->getFileNameExtension($element);
            $previousStorageKey = $this->fileService->getStorageBucketKey($upload->getUploadTarget(), $previousExtension);
        }

        return new MergeFileChunksOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $uploadKeys,
            $this->emberNexusConfiguration->getFileS3StorageBucket(),
            $previousStorageKey,
            $this->fileService->getStorageBucketKey($upload->getUploadTarget(), $upload->getExtension())
        );
    }
}
