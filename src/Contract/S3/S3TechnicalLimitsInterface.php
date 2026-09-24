<?php

declare(strict_types=1);

namespace App\Contract\S3;

/**
 * Hard limits of the S3 storage backend itself. Operator configuration (`file.*`) may only be more restrictive,
 * see {@see \App\Service\S3TechnicalLimitsValidator}.
 */
interface S3TechnicalLimitsInterface
{
    public function getMinChunkSizeInBytes(): int;

    public function getMaxChunkCount(): int;

    public function getMaxObjectSizeInBytes(): int;

    /**
     * Also limits a single server-side copy; larger objects require a multipart upload.
     */
    public function getMaxSinglePutSizeInBytes(): int;
}
