<?php

declare(strict_types=1);

namespace App\Type\Request;

final readonly class PartialUploadRequest
{
    /**
     * @param resource $content
     */
    public function __construct(
        private mixed $content,
        private string $contentType,
        private int $uploadOffset,
        private ?bool $isUploadComplete = false,
        private ?int $contentLength = null,
    ) {
    }

    /**
     * @return resource
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getUploadOffset(): int
    {
        return $this->uploadOffset;
    }

    public function isUploadComplete(): ?bool
    {
        return $this->isUploadComplete;
    }

    public function getContentLength(): ?int
    {
        return $this->contentLength;
    }
}
