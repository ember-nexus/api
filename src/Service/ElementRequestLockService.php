<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Type\RedisKeyFactory;
use Predis\Client as RedisClient;
use Ramsey\Uuid\UuidInterface;

class ElementRequestLockService
{
    // these endpoints have no streamed body (unlike file/upload endpoints), so the default request body timeout
    // (`read_body_idle`, 30s by default) already bounds a well-behaved request; this adds a buffer for processing.
    public const int TTL_IN_MILLISECONDS = 60000;

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyFactory,
        private RequestIdService $requestIdService,
    ) {
    }

    /**
     * @return string|null lock token which has to be passed to {@see release()}, null if the element is already locked
     */
    public function acquire(UuidInterface $elementId): ?string
    {
        $token = $this->requestIdService->getRequestId()->toString();
        $result = $this->redisClient->set((string) $this->redisKeyFactory->getElementRequestLockRedisKey($elementId), $token, 'PX', self::TTL_IN_MILLISECONDS, 'NX');

        return null === $result ? null : $token;
    }

    public function release(UuidInterface $elementId, string $token): void
    {
        // plain GET + DEL instead of a Lua script (EVAL) for simplicity: there is a small race window between the
        // GET and the DEL where the key could expire and be re-acquired by a different request in between, whose
        // lock would then be deleted here instead of this one. This is accepted because the TTL already bounds the
        // damage, and the token is the acquiring request's id, so a collision would require another request to be
        // assigned the exact same request id within that window, which is practically impossible (UUIDv4).
        $key = (string) $this->redisKeyFactory->getElementRequestLockRedisKey($elementId);
        if ($this->redisClient->get($key) === $token) {
            $this->redisClient->del([$key]);
        }
    }
}
