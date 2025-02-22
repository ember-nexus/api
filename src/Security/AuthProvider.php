<?php

declare(strict_types=1);

namespace App\Security;

use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Type\RedisPrefixType;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AuthProvider
{
    private bool $isAnonymous;
    private UuidInterface $userId;
    private ?UuidInterface $tokenId = null;
    private ?string $hashedToken = null;

    public function __construct(
        private ParameterBagInterface $bag,
        private TokenGenerator $tokenGenerator,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
        $anonymousUserId = $this->bag->get('anonymousUserUUID');
        if (!is_string($anonymousUserId)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('anonymousUserUUID must be set to a valid UUID');
        }
        $this->userId = UuidV4::fromString($anonymousUserId);
        $this->isAnonymous = true;
    }

    public function getRedisTokenKeyFromHashedToken(string $hashedToken): string
    {
        return sprintf(
            '%s%s',
            RedisPrefixType::TOKEN->value,
            $hashedToken
        );
    }

    public function getRedisTokenKeyFromRawToken(string $rawToken): string
    {
        return $this->getRedisTokenKeyFromHashedToken($this->tokenGenerator->hashToken($rawToken));
    }

    public function setUserAndToken(
        UuidInterface $userId,
        ?UuidInterface $tokenId = null,
        ?string $hashedToken = null,
        bool $isAnonymous = false,
    ): static {
        $this->userId = $userId;
        $this->tokenId = $tokenId;
        $this->hashedToken = $hashedToken;
        $this->isAnonymous = $isAnonymous;

        return $this;
    }

    public function isAnonymous(): bool
    {
        return $this->isAnonymous;
    }

    public function getUserId(): UuidInterface
    {
        return $this->userId;
    }

    public function getHashedToken(): ?string
    {
        return $this->hashedToken;
    }

    public function getTokenId(): ?UuidInterface
    {
        return $this->tokenId;
    }
}
