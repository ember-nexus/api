<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use EmberNexusBundle\Service\EmberNexusConfiguration;

/**
 * Single point of truth for `file.maxFileSizeInBytes`, which is also advertised to clients.
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
