<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\S3\MergeFileChunksOperationInterface;
use App\Contract\UploadInterface;
use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Factory\Type\S3\MergeFileChunksOperationFactory;
use HashContext;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;

/**
 * Turns a completed upload into the file of its target element: verifies size and digest, merges the chunks and
 * removes the upload.
 */
class UploadFinalizationService
{
    public function __construct(
        private ElementManager $elementManager,
        private EventDispatcherInterface $eventDispatcher,
        private MergeFileChunksOperationFactory $mergeFileChunksOperationFactory,
        private S3Service $s3Service,
        private UploadService $uploadService,
        private DigestService $digestService,
        private FileSizeLimitService $fileSizeLimitService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
        private LoggerInterface $logger,
        private ElementFileDeletionService $elementFileDeletionService,
        private IncrementalHashService $incrementalHashService,
        private ElementService $elementService,
        private FileCreationLockService $fileCreationLockService,
    ) {
    }

    /**
     * A verdict about the upload as a whole (size limit, inconsistent state, digest) deletes the upload, as nothing is
     * left to resume. Any other failure while merging the chunks and updating the element (e.g. S3 or a database not
     * being reachable) is not the client's fault: the upload is put back to unfinalized in the graph (not complete,
     * hash state including all chunks) and the exception is thrown as usual. All chunks are still stored, so the
     * client completes the upload again with a `PATCH` without body, `Upload-Complete: ?1` and the offset reported by
     * `HEAD`.
     *
     * @param HashContext $hashContext           hash of the whole uploaded file, including the completing chunk
     * @param string|null $reprDigestHeaderValue `Repr-Digest` header of the request which completed the upload; it
     *                                           describes the whole file, unlike `Content-Digest`, which only describes
     *                                           the body of the last request and is therefore not used here
     */
    public function finalize(UploadInterface $upload, HashContext $hashContext, ?string $reprDigestHeaderValue = null): void
    {
        // serialized first, the final hash can not be continued
        $hashState = $this->incrementalHashService->serializeContextForStorage($hashContext);
        $hash = $this->incrementalHashService->finalize($hashContext);

        $element = $this->elementManager->getElementOrFail($upload->getUploadTarget());

        // size and digest are verified before merging, so an existing file is never overwritten on a mismatch
        $mergeFileChunksOperation = $this->mergeFileChunksOperationFactory->createMergeFileOperationFromUpload($upload);

        try {
            $this->fileSizeLimitService->assertWithinMaxFileSize($upload->getUploadOffset());
        } catch (Client400BadContentException $exception) {
            $this->discardUpload($upload, $mergeFileChunksOperation);

            throw $exception;
        }

        // the stored chunks must add up to the offset which was hashed, otherwise the merged file can not be the upload
        $chunksContentLength = $this->s3Service->getChunksContentLength($mergeFileChunksOperation);
        if ($chunksContentLength !== $upload->getUploadOffset()) {
            $this->logger->error(sprintf(
                'Upload %s is inconsistent: stored chunks have %s bytes, but the upload offset is %d bytes, deleting it.',
                $upload->getId()->toString(),
                $chunksContentLength ?? 'an unknown number of',
                $upload->getUploadOffset()
            ));
            $this->discardUpload($upload, $mergeFileChunksOperation);

            throw $this->client409ConflictExceptionFactory->createFromDetail('Upload state is inconsistent and was deleted, please restart the upload.');
        }

        if (null !== $reprDigestHeaderValue) {
            try {
                $this->verifyReprDigest($reprDigestHeaderValue, $hash);
            } catch (Client400BadContentException $exception) {
                $this->discardUpload($upload, $mergeFileChunksOperation);

                throw $exception;
            }
        }

        // the same lock POST /{id}/file uses, so the merge and property write below can not race a concurrent file
        // creation for the same element; a PUT is not affected, its overwrite is intentionally lock-free
        $lockToken = $this->fileCreationLockService->acquire($upload->getUploadTarget());
        if (null === $lockToken) {
            $this->discardUpload($upload, $mergeFileChunksOperation);

            throw $this->client409ConflictExceptionFactory->createFromDetail(sprintf("Another request is currently creating the file of element with id '%s'; upload was deleted, please restart it.", $upload->getUploadTarget()->toString()));
        }

        try {
            // re-checked as tightly as possible before the merge: something else may have created a file for the
            // target while this upload's chunks were being appended. An upload which already replaces an existing
            // file (started by PUT) is expected to still find hasFile === true, so it is not checked here.
            if (!$upload->targetHadFileAtCreation() && $this->elementService->hasFile($element)) {
                $this->discardUpload($upload, $mergeFileChunksOperation);

                throw $this->client409ConflictExceptionFactory->createFromDetail(sprintf("Element with id '%s' already has an associated file; upload was deleted, please restart it.", $upload->getUploadTarget()->toString()));
            }

            try {
                $mergedContentLength = $this->s3Service->mergeFileChunks($mergeFileChunksOperation);
                $mergedMimeType = $this->s3Service->getMimeTypeFromMergeFileChunksOperation($mergeFileChunksOperation);

                $element->addProperty('file', [
                    'contentLength' => $mergedContentLength,
                    'extension' => $upload->getExtension(),
                    'mimeType' => $mergedMimeType,
                    'hash' => [
                        FileHashService::ALGORITHM => $hash,
                    ],
                ]);
                $element->addProperty('hasFile', true);
                $this->elementManager->merge($element);
                $this->elementManager->flush();
            } catch (Throwable $throwable) {
                $this->markUploadAsUnfinalized($upload, $hashState);

                throw $throwable;
            }
        } finally {
            $this->fileCreationLockService->release($upload->getUploadTarget(), $lockToken);
        }

        // only after the flush the element points to the merged object, so the previous one can go
        $this->elementFileDeletionService->deletePreviousFileAfterReplace($mergeFileChunksOperation);
        $this->s3Service->deleteFileChunks($mergeFileChunksOperation);
        $this->uploadService->deleteUpload($upload);
        $this->elementManager->flush();

        $this->eventDispatcher->dispatch(new ElementFileReplaceEvent($upload->getUploadTarget()));
    }

    // best effort, the failure which is being handled is the one which matters
    private function markUploadAsUnfinalized(UploadInterface $upload, string $hashState): void
    {
        try {
            $isUnfinalized = $this->uploadService->markUploadAsUnfinalized($upload, $hashState);
            if (!$isUnfinalized) {
                $this->logger->error(sprintf('Unable to put upload %s back to unfinalized after a failed finalization, it does not exist or is not complete anymore.', $upload->getId()->toString()));
            }
        } catch (Throwable $throwable) {
            $this->logger->error(sprintf('Unable to put upload %s back to unfinalized after a failed finalization: %s', $upload->getId()->toString(), $throwable->getMessage()));
        }
    }

    // a completed upload which is invalid as a whole (digest, size limit, inconsistent state) leaves nothing to resume,
    // so chunks and upload node are removed together
    private function discardUpload(UploadInterface $upload, MergeFileChunksOperationInterface $mergeFileChunksOperation): void
    {
        $this->s3Service->deleteFileChunks($mergeFileChunksOperation);
        $this->uploadService->deleteUpload($upload);
        $this->elementManager->flush();
    }

    private function verifyReprDigest(string $reprDigestHeaderValue, string $actualHash): void
    {
        $expectedHash = $this->digestService->parseSha256HexFromHeaderValue($reprDigestHeaderValue);
        if (null === $expectedHash) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf("Could not verify upload: 'Repr-Digest' header '%s' does not declare a supported digest algorithm; only 'sha-256' is supported.", $reprDigestHeaderValue));
        }
        if ($expectedHash !== $actualHash) {
            throw $this->client400BadContentExceptionFactory->createFromDetail('Could not verify upload: the declared digest does not match the uploaded file\'s actual content.');
        }
    }
}
