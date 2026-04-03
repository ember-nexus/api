<?php

declare(strict_types=1);

namespace App\Type\S3;

class UploadFileOperation
{
    /**
     * @param resource $content
     */
    public function __construct(
        private readonly string $uploadBucket,
        private readonly string $uploadKey,
        private readonly string $storageBucket,
        private readonly ?string $previousStorageKey,
        private readonly string $storageKey,
        private readonly mixed $content,
        private readonly ?int $contentLength,
        private readonly string $mimeType,
    ) {
    }

    public function getUploadBucket(): string
    {
        return $this->uploadBucket;
    }

    public function getUploadKey(): string
    {
        return $this->uploadKey;
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

    /**
     * @return resource
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    public function getContentLength(): ?int
    {
        return $this->contentLength;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }
}
