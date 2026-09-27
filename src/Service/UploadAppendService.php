<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\Request\PartialUploadRequestInterface;
use App\Contract\UploadInterface;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Factory\Type\S3\UploadFileChunkOperationFactory;
use App\Factory\Type\UploadFactory;
use HashContext;
use Throwable;

/**
 * Appends the body of a `PATCH` request to an upload and, if the request completes the upload, turns it into the file
 * of its target. The caller has to hold the lock of the upload and has to pass a freshly loaded upload.
 */
class UploadAppendService
{
    public function __construct(
        private UploadFactory $uploadFactory,
        private UploadService $uploadService,
        private UploadFinalizationService $uploadFinalizationService,
        private UploadChunkValidator $uploadChunkValidator,
        private UploadFileChunkOperationFactory $uploadFileChunkOperationFactory,
        private FileOperationFactory $fileOperationFactory,
        private S3Service $s3Service,
        private IncrementalHashService $incrementalHashService,
        private FileService $fileService,
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
        private ElementManager $elementManager,
        private ElementService $elementService,
    ) {
    }

    /**
     * Returns the upload as it is after the request.
     *
     * A completing request without body finishes an upload which already has chunks without storing anything: S3
     * does not accept empty parts. This is also how a client retries the completion of an upload whose finalization
     * failed, see {@see UploadFinalizationService::finalize()}.
     */
    public function append(UploadInterface $upload, PartialUploadRequestInterface $partialUploadRequest, ?string $reprDigestHeaderValue): UploadInterface
    {
        // an upload whose target had no file when it was created (i.e. started by POST) must still find none: if it
        // does, something else (e.g. a concurrent PUT) created one while this upload was in progress, and it can no
        // longer complete safely. An upload which already replaces an existing file (started by PUT) is expected to
        // still find hasFile === true, so it is not checked here; racing it further is accepted (PUT races are wanted).
        if (!$upload->targetHadFileAtCreation()) {
            $targetElement = $this->elementManager->getElementOrFail($upload->getUploadTarget());
            if ($this->elementService->hasFile($targetElement)) {
                throw $this->client409ConflictExceptionFactory->createFromDetail(sprintf("Element with id '%s' already has an associated file; this upload can no longer complete and should be discarded.", $upload->getUploadTarget()->toString()));
            }
        }

        if ($partialUploadRequest->getUploadOffset() !== $upload->getUploadOffset()) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('Offset from request does not match offset of resource.', additionalProperties: ['expectedOffset' => $upload->getUploadOffset(), 'providedOffset' => $partialUploadRequest->getUploadOffset()]);
        }

        $isFinalChunk = true === $partialUploadRequest->isUploadComplete();
        $declaredChunkLength = $partialUploadRequest->getContentLength();
        $isEmptyChunk = 0 === ($declaredChunkLength ?? $this->getBufferedContentLength($partialUploadRequest));

        if ($isEmptyChunk && !$isFinalChunk) {
            // a no-op which just reports the current state, e.g. to check the offset
            $this->closeResource($partialUploadRequest->getContent());

            return $upload;
        }

        // reject obviously invalid chunks before anything is sent to S3
        if (null !== $declaredChunkLength) {
            $this->uploadChunkValidator->assertValidChunk($declaredChunkLength, $isFinalChunk, $upload->getUploadOffset(), $upload->getUploadLength());
        }

        // hashed before the upload to S3, see IncrementalHashService
        $resource = $partialUploadRequest->getContent();
        $hashState = $upload->getHashState();
        $hashContext = null !== $hashState
            ? $this->incrementalHashService->unserializeContextFromStorage($hashState)
            : $this->incrementalHashService->createContext(FileHashService::ALGORITHM);
        $this->incrementalHashService->updateFromResource($hashContext, $resource);

        $chunkId = null;
        $chunkLength = 0;
        if ($isEmptyChunk && [] !== $upload->getChunkIds()) {
            $this->closeResource($resource);
        } else {
            // every attempt writes its own object, so that a concurrent attempt for the same chunk can not overwrite it
            $chunkId = $this->fileService->generateUploadChunkId();
            $chunkLength = $this->s3Service->uploadFileChunk($this->uploadFileChunkOperationFactory->createUploadFileChunkOperationFromPartialUploadRequest($partialUploadRequest, $upload, $chunkId));
            $this->closeResource($resource);
        }

        try {
            // the declared Content-Length was already checked before the upload, this is the authoritative check
            $this->uploadChunkValidator->assertValidChunk($chunkLength, $isFinalChunk, $upload->getUploadOffset(), $upload->getUploadLength());
            $nextUpload = $this->createNextUpload($upload, $isFinalChunk, $chunkLength, $chunkId, $hashContext);
            $this->assertOffsetUnchanged($upload, $nextUpload);
        } catch (Throwable $throwable) {
            // the chunk is not part of the upload, so nothing else references its object
            if (null !== $chunkId) {
                $this->s3Service->deleteFile($this->fileOperationFactory->createFileOperationFromUpload($upload, $upload->getAlreadyUploadedChunks() + 1, $chunkId));
            }

            throw $throwable;
        }

        if ($isFinalChunk) {
            $this->uploadFinalizationService->finalize($nextUpload, $hashContext, $reprDigestHeaderValue);
        }

        return $nextUpload;
    }

    private function createNextUpload(UploadInterface $upload, bool $isFinalChunk, int $chunkLength, ?string $chunkId, HashContext $hashContext): UploadInterface
    {
        $nextUpload = $upload;
        if (null !== $chunkId) {
            // the hash state is only kept for chunks which are followed by another one, the finalization uses the hash
            // context directly
            $nextUpload = $this->uploadFactory->addNewChunkToUpload($upload, $chunkLength, $chunkId, $isFinalChunk ? null : $this->incrementalHashService->serializeContextForStorage($hashContext));
        }

        return $isFinalChunk ? $this->uploadFactory->markUploadAsComplete($nextUpload) : $nextUpload;
    }

    // defense in depth, the lock may have expired while this request was still uploading its chunk
    private function assertOffsetUnchanged(UploadInterface $upload, UploadInterface $nextUpload): void
    {
        if (!$this->uploadService->appendChunkIfOffsetMatches($upload, $nextUpload)) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('Upload was modified by another request while this chunk was uploaded, please check the offset and retry.');
        }
    }

    // the request body is buffered by the request factory, so its size is known even without `Content-Length`
    private function getBufferedContentLength(PartialUploadRequestInterface $partialUploadRequest): int
    {
        return \Safe\fstat($partialUploadRequest->getContent())['size'];
    }

    /**
     * The S3 client may already have closed the resource while uploading it.
     *
     * @param resource $resource
     */
    private function closeResource($resource): void
    {
        /** @psalm-suppress RedundantConditionGivenDocblockType */
        if (is_resource($resource)) {
            \Safe\fclose($resource);
        }
    }
}
