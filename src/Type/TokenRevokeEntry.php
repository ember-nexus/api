<?php

declare(strict_types=1);

namespace App\Type;

use DateTimeInterface;

readonly class TokenRevokeEntry
{
    public function __construct(
        private Uuid $tokenId,
        private DateTimeInterface $tokenCreated,
        private ?DateTimeInterface $tokenExpirationDate,
        private Uuid $userId,
        private string $userUniqueIdentifier,
        private string $tokenHash,
    ) {
    }

    public function getTokenId(): Uuid
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

    public function getUserId(): Uuid
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
