<?php

declare(strict_types=1);

namespace App\Type\S3;

final readonly class DeleteFileOperation
{
    public function __construct(
        private string $bucket,
        private string $key,
    ) {
    }

    public function getBucket(): string
    {
        return $this->bucket;
    }

    public function getKey(): string
    {
        return $this->key;
    }

}
