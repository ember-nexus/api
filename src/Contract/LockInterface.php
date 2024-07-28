<?php

declare(strict_types=1);

namespace App\Contract;

interface LockInterface
{
    public function getLockKey(): string;

    public function getLockValue(): string;

    public function getLockTimeoutInSeconds(): ?int;

    public function isLocked(): bool;

    public function setLocked(bool $locked): self;
}
