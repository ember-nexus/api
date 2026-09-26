<?php

declare(strict_types=1);

namespace App\Factory\Type\S3;

use App\Contract\S3\MergeFileChunksOperationInterface;
use App\Contract\UploadInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\FileService;
use App\Type\S3\MergeFileChunksOperation;
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

    public function createMergeFileOperationFromUpload(UploadInterface $upload): MergeFileChunksOperationInterface
    {
        if (false === $upload->isUploadComplete()) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("'MergeFileChunksOperation' requires 'Upload' to be complete.");
        }

        $uploadKeys = [];
        $uploadId = $upload->getId();
        foreach ($upload->getChunkIds() as $index => $chunkId) {
            $uploadKeys[] = $this->fileService->getUploadBucketKey($uploadId, $index + 1, $chunkId);
        }

        $element = $this->elementManager->getElementOrFail($upload->getUploadTarget());

        $previousStorageKey = null;
        if ($this->elementService->hasFile($element)) {
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
