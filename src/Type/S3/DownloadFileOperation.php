<?php

declare(strict_types=1);

namespace App\Type\S3;

final readonly class DownloadFileOperation
{
    public function __construct(
        private string $bucket,
        private string $key,
        private string $targetPath,
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

    public function getTargetPath(): string
    {
        return $this->targetPath;
    }
}
