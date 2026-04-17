<?php

declare(strict_types=1);

namespace App\Contract\S3;

interface MergeFileChunksOperationInterface
{
    public function getUploadBucket(): string;

    /**
     * @return list<string>
     */
    public function getUploadKeys(): array;

    public function getStorageBucket(): string;

    public function getPreviousStorageKey(): ?string;

    public function getStorageKey(): string;
}
