<?php

declare(strict_types=1);

namespace App\Type\S3;

final readonly class MergeFileChunksOperation
{
    /**
     * @param list<string> $uploadKeys
     */
    public function __construct(
        private string $uploadBucket,
        private array $uploadKeys,
        private string $storageBucket,
        private string $storageKey,
        private string $mimeType,
    ) {
    }

    public function getUploadBucket(): string
    {
        return $this->uploadBucket;
    }

    /**
     * @return list<string>
     */
    public function getUploadKeys(): array
    {
        return $this->uploadKeys;
    }

    public function getStorageBucket(): string
    {
        return $this->storageBucket;
    }

    public function getStorageKey(): string
    {
        return $this->storageKey;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }
}
