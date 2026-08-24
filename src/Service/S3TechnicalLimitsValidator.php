<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\S3\S3TechnicalLimitsInterface;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use LogicException;

/**
 * Validates the operator-configurable `file.*` upload/storage settings ({@see EmberNexusConfiguration}) against
 * the hard technical limits of the underlying storage backend ({@see S3TechnicalLimitsInterface}). Configuration
 * may only be more restrictive than the technical limits, never less; a violation is a startup-time
 * configuration error, not a runtime one.
 */
class S3TechnicalLimitsValidator
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private S3TechnicalLimitsInterface $s3TechnicalLimits,
    ) {
    }

    public function validate(): void
    {
        $this->validateMinChunkSize();
        $this->validateMaxChunkCount();
        $this->validateMaxObjectSize();
    }

    private function validateMinChunkSize(): void
    {
        $configuredMinChunkSize = $this->emberNexusConfiguration->getFileUploadMinChunkSizeInBytes();
        $technicalMinChunkSize = $this->s3TechnicalLimits->getMinChunkSizeInBytes();

        if ($configuredMinChunkSize < $technicalMinChunkSize) {
            throw new LogicException(sprintf("Configured 'file.uploadMinChunkSizeInBytes' (%d) can not be smaller than the storage backend's technical minimum chunk size (%d).", $configuredMinChunkSize, $technicalMinChunkSize));
        }
    }

    private function validateMaxChunkCount(): void
    {
        $chunkDigitsLength = $this->emberNexusConfiguration->getFileUploadChunkDigitsLength();
        $configuredMaxChunkCount = 10 ** $chunkDigitsLength;
        $technicalMaxChunkCount = $this->s3TechnicalLimits->getMaxChunkCount();

        if ($configuredMaxChunkCount > $technicalMaxChunkCount) {
            throw new LogicException(sprintf("Configured 'file.uploadChunkDigitsLength' (%d) allows up to %d chunks, which exceeds the storage backend's technical maximum of %d chunks.", $chunkDigitsLength, $configuredMaxChunkCount, $technicalMaxChunkCount));
        }
    }

    private function validateMaxObjectSize(): void
    {
        $configuredMaxFileSize = $this->emberNexusConfiguration->getFileMaxFileSizeInBytes();
        $technicalMaxObjectSize = $this->s3TechnicalLimits->getMaxObjectSizeInBytes();

        if ($configuredMaxFileSize > $technicalMaxObjectSize) {
            throw new LogicException(sprintf("Configured 'file.maxFileSizeInBytes' (%d) can not be larger than the storage backend's technical maximum object size (%d).", $configuredMaxFileSize, $technicalMaxObjectSize));
        }
    }
}
