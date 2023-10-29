<?php

namespace App\EventSystem\EntityManager\EventListener;

use App\Contract\NodeElementInterface;
use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\Security\AuthProvider;
use Predis\Client;
use Psr\Log\LoggerInterface;

class ExpireTokenOnTokenDeleteEventListener
{
    public function __construct(
        private AuthProvider $authProvider,
        private Client $redisClient,
        private LoggerInterface $logger
    ) {
    }

    public function onElementPostDeleteEvent(ElementPostDeleteEvent $event): void
    {
        $oldToken = $event->getElement();
        if (!($oldToken instanceof NodeElementInterface)) {
            return;
        }
        if ('Token' !== $oldToken->getLabel()) {
            return;
        }
        $hashedToken = $this->authProvider->getHashedToken();
        if (null === $hashedToken) {
            $this->logger->critical(
                'Unable to expire deleted token from redis.',
                [
                    'tokenUuid' => $oldToken->getIdentifier()?->toString(),
                    'tokenProperties' => $oldToken->getProperties(),
                    'userUuid' => $this->authProvider->getUserUuid()->toString(),
                ]
            );

            return;
        }
        $this->redisClient->expire(
            $this->authProvider->getRedisTokenKeyFromHashedToken(
                $hashedToken
            ), 0);
    }
}
