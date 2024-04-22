<?php

declare(strict_types=1);

namespace App\Type;

use Ramsey\Uuid\UuidInterface;

class UserIdAndTokenIdObject
{
    public function __construct(
        private UuidInterface $userId,
        private UuidInterface $tokenId,
    ) {
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

    public function getTokenId(): UuidInterface
    {
        return $this->tokenId;
    }

    public function setTokenId(UuidInterface $tokenId): self
    {
        $this->tokenId = $tokenId;

        return $this;
    }
}
