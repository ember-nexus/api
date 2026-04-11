<?php

declare(strict_types=1);

namespace App\Type\S3;

final readonly class MergeFileChunksOperation
{
    public function __construct(
        private string $bucket,
        private string $key,
    ) {
    }
}
