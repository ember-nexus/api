<?php

namespace App\EventListener;

use App\Exception\SecurityException;
use App\Security\AuthProvider;
use App\Security\TokenGenerator;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class ApiKeyCheckOnKernelRequestEventListener
{
    public function __construct(
        private TokenGenerator $tokenGenerator,
        private CypherEntityManager $cypherEntityManager,
        private AuthProvider $authProvider
    ) {
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$event->getRequest()->headers->has('Authorization')) {
            return;
        }

        $tokenParts = explode(' ', $event->getRequest()->headers->get('Authorization'), 2);
        if (2 !== count($tokenParts)) {
            throw new SecurityException('Invalid authorization token');
        }
        $token = $tokenParts[1];
        if (!str_starts_with($token, 'secret-token:')) {
            throw new SecurityException('Invalid authorization token');
        }

        $res = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                'MATCH (user:User)-[:OWNS]->(token:Token {hash: $hash}) RETURN user.id, token.id LIMIT 1',
                [
                    'hash' => $this->tokenGenerator->hashToken($token),
                ]
            )
        );

        if (0 === count($res)) {
            throw new SecurityException('Invalid authorization token');
        }

        $userUuid = Uuid::fromString($res->first()->get('user.id'));
        $tokenUuid = Uuid::fromString($res->first()->get('token.id'));
        $this->authProvider->setUserAndToken($userUuid, $tokenUuid);
    }
}
