<?php

declare(strict_types=1);

namespace App\Type\S3;

final readonly class UploadFileChunkOperation
{
    /**
     * @param resource $content
     */
    public function __construct(
        private string $uploadBucket,
        private string $uploadKey,
        private mixed  $content,
        private ?int   $contentLength,
        private string $mimeType,
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
