<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use EmberNexusBundle\Service\EmberNexusConfiguration;

/**
 * Single point of truth for `file.maxFileSizeInBytes`, the operator-configured upper bound on a stored file.
 *
 * The limit is advertised to clients through the `max-size` field of the `Upload-Limit` response header and
 * through `/instance-configuration`, so every path which writes a file has to agree on it. API requests reject an
 * oversized file with `400 Bad Request`; `backup:load` reports it and moves on to the next file instead, which is
 * why both a throwing and a predicate form exist.
 */
class FileSizeLimitService
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private Client400BadContentExceptionFactory $client400BadContentExceptionFactory,
    ) {
    }

    public function getMaxFileSizeInBytes(): int
    {
        return $this->emberNexusConfiguration->getFileMaxFileSizeInBytes();
    }

    public function exceedsMaxFileSize(int $contentLengthInBytes): bool
    {
        return $contentLengthInBytes > $this->getMaxFileSizeInBytes();
    }

    public function assertWithinMaxFileSize(int $contentLengthInBytes): void
    {
        if (!$this->exceedsMaxFileSize($contentLengthInBytes)) {
            return;
        }

        throw $this->client400BadContentExceptionFactory->createFromDetail(sprintf('Uploaded file has to be at most %d bytes long, got %d.', $this->getMaxFileSizeInBytes(), $contentLengthInBytes));
    }
}
