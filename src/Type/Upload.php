<?php

declare(strict_types=1);

namespace App\Type;

use App\Contract\UploadInterface;
use DateTime;
use Ramsey\Uuid\UuidInterface;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
final readonly class Upload implements UploadInterface
{
    public function __construct(
        private UuidInterface $id,
        private ?int $uploadLength,
        private int $uploadOffset,
        private bool $uploadComplete,
        private UuidInterface $uploadTarget,
        private int $alreadyUploadedChunks,
        private UuidInterface $uploadOwner,
        private string $extension,
        private DateTime $expires,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getUploadLength(): ?int
    {
        return $this->uploadLength;
    }

    public function getUploadOffset(): int
    {
        return $this->uploadOffset;
    }

    public function isUploadComplete(): bool
    {
        return $this->uploadComplete;
    }

    public function getUploadTarget(): UuidInterface
    {
        return $this->uploadTarget;
    }

    public function getAlreadyUploadedChunks(): int
    {
        return $this->alreadyUploadedChunks;
    }

    public function getUploadOwner(): UuidInterface
    {
        return $this->uploadOwner;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function getExpires(): DateTime
    {
        return $this->expires;
    }
}
