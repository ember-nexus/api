<?php

declare(strict_types=1);

namespace App\Contract;

use DateTime;
use Ramsey\Uuid\UuidInterface;

interface UploadInterface
{
    public function getId(): UuidInterface;

    public function getUploadLength(): ?int;

    public function getUploadOffset(): int;

    public function isUploadComplete(): bool;

    public function getUploadTarget(): UuidInterface;

    public function getAlreadyUploadedChunks(): int;

    public function getUploadOwner(): UuidInterface;

    public function getExtension(): string;

    public function getExpires(): DateTime;

    /**
     * Base64-encoded, serialized {@see \HashContext}: the running hash of every chunk uploaded for this upload
     * so far, computed as each chunk was streamed to S3, resumed from here on the next chunk. Null until the
     * first chunk with actual content has been uploaded.
     */
    public function getHashState(): ?string;
}
