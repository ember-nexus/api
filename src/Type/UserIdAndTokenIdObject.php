<?php

declare(strict_types=1);

namespace App\Type;

class UserIdAndTokenIdObject
{
    public function __construct(
        private Uuid $userId,
        private Uuid $tokenId,
    ) {
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function setUserId(Uuid $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getTokenId(): Uuid
    {
        return $this->tokenId;
    }

    public function setTokenId(Uuid $tokenId): static
    {
        $this->tokenId = $tokenId;

        return $this;
    }
}
