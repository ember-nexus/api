<?php

declare(strict_types=1);

namespace App\Contract;

use App\Type\Uuid;
use DateTime;

interface UploadInterface
{
    public function getId(): Uuid;

    public function getUploadLength(): ?int;

    public function getUploadOffset(): int;

    public function isUploadComplete(): bool;

    public function getUploadTarget(): Uuid;

    public function getAlreadyUploadedChunks(): int;

    /**
     * Ids of the accepted chunk objects, in upload order.
     *
     * @return list<string>
     */
    public function getChunkIds(): array;

    /**
     * Id of the chunk which was accepted last, null if there is none; unique per chunk attempt.
     */
    public function getLastChunkId(): ?string;

    public function getUploadOwner(): Uuid;

    public function getExtension(): string;

    public function getExpires(): DateTime;

    /**
     * Base64-encoded, serialized {@see \HashContext} of all chunks uploaded so far; null until the first
     * non-empty chunk.
     */
    public function getHashState(): ?string;

    /**
     * Whether the target already had a file when this upload was created (a resumable replace started through
     * `PUT`).
     */
    public function targetHadFileAtCreation(): bool;
}
