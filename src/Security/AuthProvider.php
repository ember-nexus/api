<?php

namespace App\Security;

use App\Factory\Exception\Server500LogicExceptionFactory;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AuthProvider
{
    private bool $isAnonymous;
    private ?UuidInterface $userUuid;
    private ?UuidInterface $tokenUuid = null;
    private ?string $hashedToken = null;

    public function __construct(
        private ParameterBagInterface $bag,
        private TokenGenerator $tokenGenerator,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory
    ) {
        $anonymousUserUuid = $this->bag->get('anonymousUserUUID');
        if (!is_string($anonymousUserUuid)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('anonymousUserUUID must be set to a valid UUID');
        }
        $this->userUuid = UuidV4::fromString($anonymousUserUuid);
        $this->isAnonymous = true;
    }

    public function getRedisTokenKeyFromHashedToken(string $hashedToken): string
    {
        return sprintf(
            'token:%s',
            $hashedToken
        );
    }

    public function getRedisTokenKeyFromRawToken(string $rawToken): string
    {
        return $this->getRedisTokenKeyFromHashedToken($this->tokenGenerator->hashToken($rawToken));
    }

    public function setUserAndToken(
        UuidInterface $userUuid = null,
        UuidInterface $tokenUuid = null,
        string $hashedToken = null,
        bool $isAnonymous = false
    ): self {
        $this->userUuid = $userUuid;
        $this->tokenUuid = $tokenUuid;
        $this->hashedToken = $hashedToken;
        $this->isAnonymous = $isAnonymous;

        return $this;
    }

    public function isAnonymous(): bool
    {
        return $this->isAnonymous;
    }

    public function getUserUuid(): ?UuidInterface
    {
        return $this->userUuid;
    }

    public function getHashedToken(): ?string
    {
        return $this->hashedToken;
    }

    public function getTokenUuid(): ?UuidInterface
    {
        return $this->tokenUuid;
    }
}
