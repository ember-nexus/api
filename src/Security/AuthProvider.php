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

    public function __construct(
        private ParameterBagInterface $bag,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory
    ) {
        $anonymousUserUuid = $this->bag->get('anonymousUserUUID');
        if (!is_string($anonymousUserUuid)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('anonymousUserUUID must be set to a valid UUID');
        }
        $this->userUuid = UuidV4::fromString($anonymousUserUuid);
        $this->isAnonymous = true;
    }

    public function setUserAndToken(
        UuidInterface $userUuid = null,
        UuidInterface $tokenUuid = null,
        bool $isAnonymous = false
    ): self {
        $this->userUuid = $userUuid;
        $this->tokenUuid = $tokenUuid;
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

    public function getTokenUuid(): ?UuidInterface
    {
        return $this->tokenUuid;
    }
}
