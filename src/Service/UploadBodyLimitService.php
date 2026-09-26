<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use EmberNexusBundle\Service\EmberNexusConfiguration;

/**
 * A single upload request (single-request upload, resumable chunk) must not exceed `file.uploadMaxChunkSizeInBytes`.
 * The `Content-Length` header is optional and can lie (e.g. chunked transfer encoding), so the actual stream is
 * bounded as well: at most cap + 1 bytes are read, more than the cap is rejected.
 */
class UploadBodyLimitService
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function assertDeclaredLengthWithinLimit(?int $declaredContentLength): void
    {
        $maxLength = $this->emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes();
        if (null !== $declaredContentLength && $declaredContentLength > $maxLength) {
            throw $this->createTooLongException($maxLength, (string) $declaredContentLength);
        }
    }

    /**
     * Returns a seekable copy of at most the maximum length, spilling to disk for large bodies.
     *
     * @param resource $resource
     *
     * @return resource
     */
    public function boundContent($resource, ?int $declaredContentLength): mixed
    {
        $this->assertDeclaredLengthWithinLimit($declaredContentLength);

        $maxLength = $this->emberNexusConfiguration->getFileUploadMaxChunkSizeInBytes();
        $bounded = \Safe\fopen('php://temp', 'r+');
        $copiedLength = \Safe\stream_copy_to_stream($resource, $bounded, $maxLength + 1);
        if ($copiedLength > $maxLength) {
            \Safe\fclose($bounded);

            throw $this->createTooLongException($maxLength, 'more');
        }
        \Safe\rewind($bounded);

        return $bounded;
    }

    private function createTooLongException(int $maxLength, string $actual): Client400BadContentException
    {
        return $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Uploaded chunk has to be at most %d bytes long, got %s.', $maxLength, $actual));
    }
}
