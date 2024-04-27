<?php

declare(strict_types=1);

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

        $oldTokenHash = $oldToken->getProperty('hash');

        if (null === $oldTokenHash) {
            $this->logger->critical(
                'Unable to expire deleted token from redis.',
                [
                    'tokenId' => $oldToken->getId()?->toString(),
                    'tokenProperties' => $oldToken->getProperties(),
                    'userId' => $this->authProvider->getUserId()->toString(),
                ]
            );

            return;
        }

        $this->redisClient->expire(
            $this->authProvider->getRedisTokenKeyFromHashedToken(
                $oldTokenHash
            ),
            0
        );
    }
}
