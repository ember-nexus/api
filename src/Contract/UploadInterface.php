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

    /**
     * Ids of the accepted chunk objects in upload order, see {@see \App\Service\FileService::getUploadBucketKey()}.
     *
     * @return list<string>
     */
    public function getChunkIds(): array;

    /**
     * Id of the chunk which was accepted last, null if there is none; unique per chunk attempt.
     */
    public function getLastChunkId(): ?string;

    public function getUploadOwner(): UuidInterface;

    public function getExtension(): string;

    public function getExpires(): DateTime;

    /**
     * Base64-encoded, serialized {@see \HashContext} of all chunks uploaded so far; null until the first
     * non-empty chunk.
     */
    public function getHashState(): ?string;
}
