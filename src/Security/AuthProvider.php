<?php

namespace App\Security;

use Ramsey\Uuid\UuidInterface;

class AuthProvider
{
    private ?UuidInterface $userUuid = null;
    private ?UuidInterface $tokenUuid = null;

    public function __construct()
    {
    }

    public function setUserAndToken(?UuidInterface $userUuid = null, ?UuidInterface $tokenUuid = null): self
    {
        $this->userUuid = $userUuid;
        $this->tokenUuid = $tokenUuid;

        return $this;
    }

    public function isAnonymous(): bool
    {
        return null !== $this->userUuid && null !== $this->tokenUuid;
    }

    public function getUserUuid(): ?UuidInterface
    {
        return $this->userUuid;
    }

    public function getTokenUuid(): ?UuidInterface
    {
        return $this->tokenUuid;
    }
}
