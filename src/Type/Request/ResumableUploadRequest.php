<?php

declare(strict_types=1);

namespace App\Type\Request;

use App\Service\FileService;
use Ramsey\Uuid\UuidInterface;

class ResumableUploadRequest {

    private UuidInterface $elementId;

    /**
     * @var resource
     */
    private mixed $content;

    private bool | null $isUploadComplete = false;
    private int | null $uploadLength = null;
    private int | null $contentLength = null;
    private string $extension = FileService::DEFAULT_EXTENSION;

    public function getElementId(): UuidInterface
    {
        return $this->elementId;
    }

    public function setElementId(UuidInterface $elementId): static
    {
        $this->elementId = $elementId;
        return $this;
    }

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

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): static
    {
        $this->extension = $extension;
        return $this;
    }

}
