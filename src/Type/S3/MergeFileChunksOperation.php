<?php

declare(strict_types=1);

namespace App\Type\S3;

use App\Contract\S3\MergeFileChunksOperationInterface;

final readonly class MergeFileChunksOperation implements MergeFileChunksOperationInterface
{
    /**
     * @param list<string> $uploadKeys
     */
    public function __construct(
        private string $uploadBucket,
        private array $uploadKeys,
        private string $storageBucket,
        private ?string $previousStorageKey,
        private string $storageKey,
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

    public function getPreviousStorageKey(): ?string
    {
        return $this->previousStorageKey;
    }

    public function getStorageKey(): string
    {
        return $this->storageKey;
    }
}
