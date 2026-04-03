<?php

declare(strict_types=1);

namespace App\Type\S3;

class UploadFileChunkOperation
{
    /**
     * @param resource $content
     */
    public function __construct(
        private readonly string $uploadBucket,
        private readonly string $uploadKey,
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
