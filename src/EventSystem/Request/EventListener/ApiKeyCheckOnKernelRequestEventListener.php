<?php

declare(strict_types=1);

namespace App\EventSystem\Request\EventListener;

use App\Factory\Exception\Client401UnauthorizedExceptionFactory;
use App\Security\AuthProvider;
use App\Security\TokenGenerator;
use App\Type\TokenStateType;
use App\Type\UserIdAndTokenIdObject;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\DateTime as LaudisDateTime;
use Predis\Client as RedisClient;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\Uuid;
use Safe\DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

use function str_starts_with;

class ApiKeyCheckOnKernelRequestEventListener
{
    public function __construct(
        private TokenGenerator $tokenGenerator,
        private CypherEntityManager $cypherEntityManager,
        private RedisClient $redisClient,
        private AuthProvider $authProvider,
        private Client401UnauthorizedExceptionFactory $client401UnauthorizedExceptionFactory
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

        $userIdAndTokenIdObject = $this->getUserIdAndTokenIdFromTokenObjectFromRedis($token);

        if (!$userIdAndTokenIdObject) {
            $userIdAndTokenIdObject = $this->getUserIdAndTokenIdObjectFromTokenFromCypher($token);
        }

        $this->authProvider->setUserAndToken(
            $userIdAndTokenIdObject->getUserId(),
            $userIdAndTokenIdObject->getTokenId(),
            $this->tokenGenerator->hashToken($token)
        );
    }

    private function getUserIdAndTokenIdObjectFromTokenFromCypher(string $token): UserIdAndTokenIdObject
    {
        $hashedToken = $this->tokenGenerator->hashToken($token);
        $res = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                'MATCH (user:User)-[:OWNS]->(token:Token {hash: $hash, state: $state}) RETURN user.id, token.id, token.expirationDate',
                [
                    'hash' => $hashedToken,
                    'state' => TokenStateType::ACTIVE->value,
                ]
            )
        );

        if (0 === count($res)) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        if (count($res) > 1) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        $userId = Uuid::fromString($res->first()->get('user.id'));
        $tokenId = Uuid::fromString($res->first()->get('token.id'));

        $tokenLifetimeInRedis = 60 * 30; // 30 minutes
        if ($res->first()->get('token.expirationDate')) {
            /**
             * @var LaudisDateTime $tokenExpirationDate
             */
            $tokenExpirationDate = $res->first()->get('token.expirationDate');
            $secondsUntilTokenExpires = $tokenExpirationDate->toDateTime()->getTimestamp() - (new DateTime())->getTimestamp();
            $tokenLifetimeInRedis = min($tokenLifetimeInRedis, $secondsUntilTokenExpires);
        }

        $redisKey = $this->authProvider->getRedisTokenKeyFromHashedToken($hashedToken);
        $this->redisClient->hset($redisKey, 'token', $tokenId->toString());
        $this->redisClient->hset($redisKey, 'user', $userId->toString());
        $this->redisClient->expire($redisKey, $tokenLifetimeInRedis);

        return new UserIdAndTokenIdObject(
            $userId,
            $tokenId
        );
    }

    private function getUserIdAndTokenIdFromTokenObjectFromRedis(string $rawToken): ?UserIdAndTokenIdObject
    {
        $data = $this->redisClient->hgetall($this->authProvider->getRedisTokenKeyFromRawToken($rawToken));

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

        return new UserIdAndTokenIdObject(
            UuidV4::fromString($data['user']),
            UuidV4::fromString($data['token'])
        );
    }

    private function extractTokenFromRequest(Request $request): string
    {
        $tokenParts = explode(' ', $request->headers->get('Authorization') ?? '', 2);
        if (2 !== count($tokenParts)) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }
        $token = $tokenParts[1];
        if (!str_starts_with($token, 'secret-token:')) {
            throw $this->client401UnauthorizedExceptionFactory->createFromTemplate();
        }

        return $token;
    }
}
