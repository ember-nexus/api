<?php

declare(strict_types=1);

namespace App\Type\Request;

class ResumableUploadRequest {

    /**
     * @var resource
     */
    private mixed $content;

    private bool | null $isUploadComplete = false;
    private int | null $uploadLength = null;
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

    public function isUploadComplete(): bool | null
    {
        return $this->isUploadComplete;
    }

    public function setIsUploadComplete(bool | null $isUploadComplete): static
    {
        $this->isUploadComplete = $isUploadComplete;
        return $this;
    }

    public function getUploadLength(): ?int
    {
        return $this->uploadLength;
    }

    public function setUploadLength(?int $uploadLength): static
    {
        $this->uploadLength = $uploadLength;
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
