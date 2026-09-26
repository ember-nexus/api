<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use EmberNexusBundle\Service\EmberNexusConfiguration;

/**
 * The rules for a single chunk of a resumable upload, shared by the creation of an upload and by appending to it. All
 * checks only need the chunk length, so they run before anything is stored (the declared length) and again with the
 * authoritative length after the chunk was written.
 */
class UploadChunkValidator
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private FileSizeLimitService $fileSizeLimitService,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
        private Client409ConflictExceptionFactory $client409ConflictExceptionFactory,
    ) {
    }

    public function assertValidChunk(int $chunkLength, bool $isFinalChunk, int $uploadOffset, ?int $uploadLength): void
    {
        $this->assertChunkSize($chunkLength, $isFinalChunk, $uploadOffset);
        $this->assertWithinDeclaredLength($chunkLength, $isFinalChunk, $uploadOffset, $uploadLength);
    }

    /**
     * Limits of the server: only the final chunk may be shorter than the minimum chunk size (S3 has a minimum part
     * size), no chunk may exceed the maximum chunk size, and the running total may not exceed the maximum file size.
     */
    public function assertChunkSize(int $chunkLength, bool $isFinalChunk, int $uploadOffset): void
    {
        $minChunkSize = $this->emberNexusConfiguration->getFileUploadMinChunkSizeInBytes();
        if (!$isFinalChunk && $chunkLength < $minChunkSize) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Uploaded chunk has to be at least %d bytes long, got %d.', $minChunkSize, $chunkLength));
        }
        $maxChunkSize = $this->emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes();
        if ($chunkLength > $maxChunkSize) {
            throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Uploaded chunk has to be at most %d bytes long, got %d.', $maxChunkSize, $chunkLength));
        }

        // rejected as soon as the running total exceeds the limit, not only on completion
        $this->fileSizeLimitService->assertWithinMaxFileSize($uploadOffset + $chunkLength);
    }

    /**
     * A request which contradicts the length declared by the client is rejected on its own; the upload keeps its
     * state and can be continued by a corrected request.
     */
    public function assertWithinDeclaredLength(int $chunkLength, bool $isFinalChunk, int $uploadOffset, ?int $uploadLength): void
    {
        if (null === $uploadLength) {
            return;
        }

        $totalLength = $uploadOffset + $chunkLength;
        if ($totalLength > $uploadLength) {
            throw $this->client409ConflictExceptionFactory->createFromDetail('Already uploaded data exceeds defined upload length.');
        }
        if ($isFinalChunk && $totalLength < $uploadLength) {
            throw $this->client409ConflictExceptionFactory->createFromDetail(sprintf('Completed upload has %d bytes, but the defined upload length is %d bytes.', $totalLength, $uploadLength));
        }
    }
}
