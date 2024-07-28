<?php

declare(strict_types=1);

namespace App\Type\Lock;

use App\Contract\LockInterface;
use App\Type\RedisPrefixType;
use Ramsey\Uuid\UuidInterface;

class FileUploadCheckLock implements LockInterface
{
    private bool $isLocked = false;

    public function __construct(
        private UuidInterface $elementId,
        private UuidInterface $userId,
    ) {
    }

    public function getElementId(): UuidInterface
    {
        return $this->elementId;
    }

    public function setElementId(UuidInterface $elementId): self
    {
        $this->elementId = $elementId;

        return $this;
    }

    public function getUserId(): UuidInterface
    {
        return $this->userId;
    }

    public function setUserId(UuidInterface $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getLockKey(): string
    {
        return sprintf('%s%s', RedisPrefixType::LOCK_FILE_UPLOAD_CHECK->value, $this->elementId);
    }

    public function getLockValue(): string
    {
        return $this->userId->toString();
    }

    public function getLockTimeoutInSeconds(): ?int
    {
        return 30;
    }

    public function isLocked(): bool
    {
        return $this->isLocked;
    }

    public function setLocked(bool $locked): LockInterface
    {
        $this->isLocked = $locked;

        return $this;
    }
}
