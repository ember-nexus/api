<?php

declare(strict_types=1);

namespace App\Factory\Type\S3;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Contract\Request\PartialUploadRequestInterface;
use App\Contract\Request\ResumableUploadRequestInterface;
use App\Contract\S3\FileOperationInterface;
use App\Contract\S3\MergeFileChunksOperationInterface;
use App\Contract\S3\UploadFileChunkOperationInterface;
use App\Contract\S3\UploadFileOperationInterface;
use App\Contract\UploadInterface;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\MimeTypeService;
use App\Service\StorageService;
use App\Type\S3\FileOperation;
use App\Type\S3\MergeFileChunksOperation;
use App\Type\S3\UploadFileChunkOperation;
use App\Type\S3\UploadFileOperation;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Ramsey\Uuid\UuidInterface;

/**
 * Builds every S3 operation value object; callers depend on this single factory instead of one per operation type.
 *
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class S3OperationFactory
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private ElementManager $elementManager,
        private ElementService $elementService,
        private MimeTypeService $mimeTypeService,
        private StorageService $storageService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    public function createFileOperationFromUpload(UploadInterface $upload, int $chunk, string $chunkId): FileOperationInterface
    {
        return new FileOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $this->storageService->getUploadBucketKey($upload->getId(), $chunk, $chunkId)
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
            $this->storageService->getStorageBucketKey($elementId, $extension)
        );
    }

    public function createUploadFileOperationFromResumableUploadRequest(ResumableUploadRequestInterface $resumableUploadRequest): UploadFileOperationInterface
    {
        if (false === $resumableUploadRequest->isUploadComplete()) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("'UploadFileOperation' requires 'ResumableUploadRequest' to contain the whole content, i.e. be a non-chunked upload request.");
        }

        $elementId = $resumableUploadRequest->getElementId();
        $element = $this->elementManager->getElementOrFail($elementId);

        $resource = $resumableUploadRequest->getContent();

        return new UploadFileOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $this->storageService->getUploadBucketKey($elementId, 0),
            $this->emberNexusConfiguration->getFileS3StorageBucket(),
            $this->elementService->getStorageKeyOfFile($element),
            $this->storageService->getStorageBucketKey($elementId, $resumableUploadRequest->getExtension()),
            $resource,
            $resumableUploadRequest->getContentLength(),
            $this->mimeTypeService->getMimeTypeFromResource($resource)
        );
    }

    /**
     * @param resource $resource
     * @param int|null $contentLength enables multipart uploads for large files in {@see \App\Service\S3Service::uploadFile()}
     */
    public function createUploadFileOperationFromElementAndResource(NodeElementInterface|RelationElementInterface $element, $resource, ?int $contentLength = null): UploadFileOperationInterface
    {
        $elementId = $element->getId();
        if (null === $elementId) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Expected element.id to not be null.');
        }

        $extension = $this->elementService->getFileNameExtension($element);

        return new UploadFileOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $this->storageService->getUploadBucketKey($elementId, 0),
            $this->emberNexusConfiguration->getFileS3StorageBucket(),
            null,
            $this->storageService->getStorageBucketKey($elementId, $extension),
            $resource,
            $contentLength,
            $this->mimeTypeService->getMimeTypeFromResource($resource)
        );
    }

    public function createUploadFileChunkOperationFromResumableUploadRequest(ResumableUploadRequestInterface $resumableUploadRequest, UuidInterface $uploadId, string $chunkId): UploadFileChunkOperationInterface
    {
        if (true === $resumableUploadRequest->isUploadComplete()) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("'UploadFileChunkOperation' requires 'ResumableUploadRequest' to contain partial content, i.e. be a chunked upload request.");
        }

        return new UploadFileChunkOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $this->storageService->getUploadBucketKey($uploadId, 1, $chunkId),
            $resumableUploadRequest->getContent(),
            $resumableUploadRequest->getContentLength(),
            'application/octet-stream'
        );
    }

    public function createUploadFileChunkOperationFromPartialUploadRequest(PartialUploadRequestInterface $partialUploadRequest, UploadInterface $upload, string $chunkId): UploadFileChunkOperationInterface
    {
        return new UploadFileChunkOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $this->storageService->getUploadBucketKey($upload->getId(), $upload->getAlreadyUploadedChunks() + 1, $chunkId),
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

    public function createMergeFileOperationFromUpload(UploadInterface $upload): MergeFileChunksOperationInterface
    {
        if (false === $upload->isUploadComplete()) {
            throw $this->client400BadContentExceptionFactory->createFromDetail("'MergeFileChunksOperation' requires 'Upload' to be complete.");
        }

        $uploadKeys = [];
        $uploadId = $upload->getId();
        foreach ($upload->getChunkIds() as $index => $chunkId) {
            $uploadKeys[] = $this->storageService->getUploadBucketKey($uploadId, $index + 1, $chunkId);
        }

        $element = $this->elementManager->getElementOrFail($upload->getUploadTarget());

        return new MergeFileChunksOperation(
            $this->emberNexusConfiguration->getFileS3UploadBucket(),
            $uploadKeys,
            $this->emberNexusConfiguration->getFileS3StorageBucket(),
            $this->elementService->getStorageKeyOfFile($element),
            $this->storageService->getStorageBucketKey($upload->getUploadTarget(), $upload->getExtension())
        );
    }
}
