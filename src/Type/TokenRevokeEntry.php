<?php

namespace App\Type;

use DateTimeInterface;
use Ramsey\Uuid\UuidInterface;

readonly class TokenRevokeEntry
{
    public function __construct(
        private UuidInterface $tokenId,
        private DateTimeInterface $tokenCreated,
        private ?DateTimeInterface $tokenExpirationDate,
        private UuidInterface $userId,
        private string $userUniqueIdentifier,
        private string $tokenHash
    ) {
    }

    public function getTokenId(): UuidInterface
    {
        return $this->tokenId;
    }

    public function getTokenCreated(): DateTimeInterface
    {
        return $this->tokenCreated;
    }

    public function getTokenExpirationDate(): ?DateTimeInterface
    {
        return $this->tokenExpirationDate;
    }

    public function getUserId(): UuidInterface
    {
        return $this->userId;
    }

    public function getUserUniqueIdentifier(): string
    {
        return $this->userUniqueIdentifier;
    }

    public function getTokenHash(): string
    {
        return $this->tokenHash;
    }
}
