<?php

declare(strict_types=1);

namespace App\Type\S3;

use App\Contract\S3\S3TechnicalLimitsInterface;

/**
 * Hard technical limits of the standard AWS S3 multipart upload protocol.
 *
 * @see https://docs.aws.amazon.com/AmazonS3/latest/userguide/qfacts.html
 */
readonly class S3TechnicalLimits implements S3TechnicalLimitsInterface
{
    private const int MIN_CHUNK_SIZE_IN_BYTES = 5 * 1024 * 1024; // 5 MiB, does not apply to the last part of an upload
    private const int MAX_CHUNK_COUNT = 10_000;
    private const int MAX_OBJECT_SIZE_IN_BYTES = 5 * 1024 * 1024 * 1024 * 1024; // 5 TiB

    public function getMinChunkSizeInBytes(): int
    {
        return self::MIN_CHUNK_SIZE_IN_BYTES;
    }

    public function getMaxChunkCount(): int
    {
        return self::MAX_CHUNK_COUNT;
    }

    public function getMaxObjectSizeInBytes(): int
    {
        return self::MAX_OBJECT_SIZE_IN_BYTES;
    }
}
