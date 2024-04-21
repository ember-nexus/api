<?php

declare(strict_types=1);

namespace App\Type;

use Ramsey\Uuid\UuidInterface;

class UserUuidAndTokenUuidObject
{
    public function __construct(
        private UuidInterface $userUuid,
        private UuidInterface $tokenUuid,
    ) {
    }

    public function getUserUuid(): UuidInterface
    {
        return $this->userUuid;
    }

    public function setUserUuid(UuidInterface $userUuid): self
    {
        $this->userUuid = $userUuid;

        return $this;
    }

    public function getTokenUuid(): UuidInterface
    {
        return $this->tokenUuid;
    }

    public function setTokenUuid(UuidInterface $tokenUuid): self
    {
        $this->tokenUuid = $tokenUuid;

        return $this;
    }
}
