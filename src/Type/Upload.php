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
    /**
     * @param list<string> $chunkIds
     */
    public function __construct(
        private UuidInterface $id,
        private ?int $uploadLength,
        private int $uploadOffset,
        private bool $uploadComplete,
        private UuidInterface $uploadTarget,
        private array $chunkIds,
        private UuidInterface $uploadOwner,
        private string $extension,
        private DateTime $expires,
        private ?string $hashState = null,
        private bool $targetHadFileAtCreation = false,
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
        return count($this->chunkIds);
    }

    /**
     * @return list<string>
     */
    public function getChunkIds(): array
    {
        return $this->chunkIds;
    }

    public function getLastChunkId(): ?string
    {
        return [] === $this->chunkIds ? null : $this->chunkIds[array_key_last($this->chunkIds)];
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

    public function getHashState(): ?string
    {
        return $this->hashState;
    }

    /**
     * Whether the target already had a file when this upload was created (a resumable replace started through
     * `PUT`). Such an upload is expected to still find `hasFile === true` right before it completes, so the
     * `hasFile` conflict checks in {@see \App\Service\UploadAppendService} and
     * {@see \App\Service\UploadFinalizationService} only apply when this is false.
     */
    public function targetHadFileAtCreation(): bool
    {
        return $this->targetHadFileAtCreation;
    }
}
