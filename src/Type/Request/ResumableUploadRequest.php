<?php

declare(strict_types=1);

namespace App\Type\Request;

use App\Contract\Request\ResumableUploadRequestInterface;
use App\Service\FileService;
use Ramsey\Uuid\UuidInterface;

final readonly class ResumableUploadRequest implements ResumableUploadRequestInterface
{
    /**
     * @param resource $content
     */
    public function __construct(
        private UuidInterface $elementId,
        private mixed $content,
        private ?bool $isUploadComplete = false,
        private ?int $uploadLength = null,
        private ?int $contentLength = null,
        private string $extension = FileService::DEFAULT_EXTENSION,
    ) {
    }

    public function getElementId(): UuidInterface
    {
        return $this->elementId;
    }

    /**
     * @return resource
     */
    public function getContent(): mixed
    {
        return $this->content;
    }

    public function isUploadComplete(): ?bool
    {
        return $this->isUploadComplete;
    }

    public function getUploadLength(): ?int
    {
        return $this->uploadLength;
    }

    public function getContentLength(): ?int
    {
        return $this->contentLength;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }
}
