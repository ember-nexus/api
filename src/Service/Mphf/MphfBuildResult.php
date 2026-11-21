<?php

declare(strict_types=1);

namespace App\Service\Mphf;

/**
 * Outcome of {@see MphfBuildService::build()}. A skipped build is no failure: another process is building already, or a
 * backup load / database reset is running.
 */
final class MphfBuildResult
{
    public function __construct(
        private bool $skipped,
        private ?string $message = null,
        private ?string $version = null,
        private int $keyCount = 0,
        private int $sizeInBytes = 0,
        private float $durationInSeconds = 0.0,
        private int $deletedFileCount = 0,
    ) {
    }

    public static function skipped(string $reason): self
    {
        return new self(true, $reason);
    }

    public function isSkipped(): bool
    {
        return $this->skipped;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getKeyCount(): int
    {
        return $this->keyCount;
    }

    public function getSizeInBytes(): int
    {
        return $this->sizeInBytes;
    }

    public function getDurationInSeconds(): float
    {
        return $this->durationInSeconds;
    }

    public function getDeletedFileCount(): int
    {
        return $this->deletedFileCount;
    }
}
