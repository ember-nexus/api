<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\S3\MergeFileChunksOperationInterface;
use App\Contract\UploadInterface;
use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Type\S3\MergeFileChunksOperationFactory;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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
    ) {
    }

    /**
     * @param string      $hash                  hash of the whole uploaded file
     * @param string|null $reprDigestHeaderValue `Repr-Digest` header of the request which completed the upload; it
     *                                           describes the whole file, unlike `Content-Digest`, which only describes
     *                                           the body of the last request and is therefore not used here
     */
    public function finalize(UploadInterface $upload, string $hash, ?string $reprDigestHeaderValue = null): void
    {
        $element = $this->elementManager->getElementOrFail($upload->getUploadTarget());

        // size and digest are verified before merging, so an existing file is never overwritten on a mismatch
        $mergeFileChunksOperation = $this->mergeFileChunksOperationFactory->createMergeFileOperationFromUpload($upload);

        try {
            $this->fileSizeLimitService->assertWithinMaxFileSize($upload->getUploadOffset());
        } catch (Client400BadContentException $exception) {
            $this->discardUpload($upload, $mergeFileChunksOperation);

            throw $exception;
        }

        if (null !== $reprDigestHeaderValue) {
            try {
                $this->verifyReprDigest($reprDigestHeaderValue, $hash);
            } catch (Client400BadContentException $exception) {
                $this->discardUpload($upload, $mergeFileChunksOperation);

                throw $exception;
            }
        }

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

        $this->s3Service->deleteFileChunks($mergeFileChunksOperation);
        $this->uploadService->deleteUpload($upload);
        $this->elementManager->flush();

        $this->eventDispatcher->dispatch(new ElementFileReplaceEvent($upload->getUploadTarget()));
    }

    // a failed completion leaves nothing to resume, so chunks and upload node are removed together
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
