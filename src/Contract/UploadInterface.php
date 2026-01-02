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
}
