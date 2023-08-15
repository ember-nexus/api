<?php

namespace App\EventSystem\Request\EventListener;

use App\Exception\ClientUnauthorizedException;
use App\Security\AuthProvider;
use App\Security\TokenGenerator;
use App\Type\UserUuidAndTokenUuidObject;
use Laudis\Neo4j\Databags\Statement;
use Predis\Client;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class ApiKeyCheckOnKernelRequestEventListener
{
    public function __construct(
        private TokenGenerator $tokenGenerator,
        private CypherEntityManager $cypherEntityManager,
        private Client $redisClient,
        private AuthProvider $authProvider
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$event->getRequest()->headers->has('Authorization')) {
            return;
        }

        $token = $this->extractTokenFromRequest($event->getRequest());

        $userUuidAndTokenUuidObject = $this->getUserUuidAndTokenUuidFromTokenObjectFromRedis($token);

        if (!$userUuidAndTokenUuidObject) {
            $userUuidAndTokenUuidObject = $this->getUserUuidAndTokenUuidObjectFromTokenFromCypher($token);
        }

        $this->authProvider->setUserAndToken(
            $userUuidAndTokenUuidObject->getUserUuid(),
            $userUuidAndTokenUuidObject->getTokenUuid()
        );
    }

    private function getUserUuidAndTokenUuidObjectFromTokenFromCypher(string $token): UserUuidAndTokenUuidObject
    {
        $hashedToken = $this->tokenGenerator->hashToken($token);
        $res = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                'MATCH (user:User)-[:OWNS]->(token:Token {hash: $hash}) RETURN user.id, token.id LIMIT 1',
                [
                    'hash' => $hashedToken,
                ]
            )
        );

        if (0 === count($res)) {
            throw new ClientUnauthorizedException('Invalid authorization token');
        }

        $userUuid = Uuid::fromString($res->first()->get('user.id'));
        $tokenUuid = Uuid::fromString($res->first()->get('token.id'));

        $redisKey = sprintf('token:%s', $hashedToken);
        $this->redisClient->hset($redisKey, 'token', $tokenUuid->toString());
        $this->redisClient->hset($redisKey, 'user', $userUuid->toString());
        $this->redisClient->expire($redisKey, 60 * 30); // 30 minutes

        return new UserUuidAndTokenUuidObject(
            $userUuid,
            $tokenUuid
        );
    }

    private function getUserUuidAndTokenUuidFromTokenObjectFromRedis(string $token): ?UserUuidAndTokenUuidObject
    {
        $data = $this->redisClient->hgetall(sprintf(
            'token:%s',
            $this->tokenGenerator->hashToken($token)
        ));

        if (0 === count($data)) {
            return null;
        }

        if (!array_key_exists('token', $data)) {
            // redis data is corrupted -> recreate redis dataset indirectly
            return null;
        }
        if (!array_key_exists('user', $data)) {
            // redis data is corrupted -> recreate redis dataset indirectly
            return null;
        }

        return new UserUuidAndTokenUuidObject(
            UuidV4::fromString($data['user']),
            UuidV4::fromString($data['token'])
        );
    }

    private function extractTokenFromRequest(Request $request): string
    {
        $tokenParts = explode(' ', $request->headers->get('Authorization') ?? '', 2);
        if (2 !== count($tokenParts)) {
            throw new ClientUnauthorizedException('Invalid authorization token');
        }
        $token = $tokenParts[1];
        if (!\str_starts_with($token, 'secret-token:')) {
            throw new ClientUnauthorizedException('Invalid authorization token');
        }

        return $token;
    }
}
