<?php

declare(strict_types=1);

namespace App\Type\Request;

class PartialUploadRequest {

    /**
     * @var resource
     */
    private mixed $content;

    private string $contentType;
    private int $uploadOffset;
    private bool | null $isUploadComplete = false;
    private int | null $contentLength = null;

    /**
     * @return resource
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    /**
     * @param resource $content
     */
    public function setContent(mixed $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function setContentType(string $contentType): static
    {
        $this->contentType = $contentType;
        return $this;
    }

    public function getUploadOffset(): int
    {
        return $this->uploadOffset;
    }

    public function setUploadOffset(int $uploadOffset): static
    {
        $this->uploadOffset = $uploadOffset;
        return $this;
    }

    public function isUploadComplete(): ?bool
    {
        return $this->isUploadComplete;
    }

    public function setUploadComplete(?bool $isUploadComplete): static
    {
        $this->isUploadComplete = $isUploadComplete;
        return $this;
    }

    public function getContentLength(): ?int
    {
        return $this->contentLength;
    }

    public function setContentLength(?int $contentLength): static
    {
        $this->contentLength = $contentLength;
        return $this;
    }

}
