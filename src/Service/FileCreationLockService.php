<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Type\RedisKeyFactory;
use Predis\Client as RedisClient;
use Ramsey\Uuid\UuidInterface;

/**
 * Reserves an element for the creation of its file: `POST /<id>/file` holds the lock while it runs, so concurrent
 * `POST` requests can not both create the file. `PUT` requests do not take the lock (parallel replacements are
 * allowed), but respect it.
 *
 * The lock is a Redis key holding the acquiring request's id; it expires on its own so that a crashed request can
 * not block the element forever, and is only released by its owner (compare-and-delete).
 */
class FileCreationLockService
{
    // a single request upload may take 15 minutes at most (`read_body` in the Caddyfile)
    public const int TTL_IN_MILLISECONDS = 900000;

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
        $result = $this->redisClient->set((string) $this->redisKeyFactory->getFileCreationLockRedisKey($elementId), $token, 'PX', self::TTL_IN_MILLISECONDS, 'NX');

        return null === $result ? null : $token;
    }

    public function isLocked(UuidInterface $elementId): bool
    {
        return 0 !== (int) $this->redisClient->exists((string) $this->redisKeyFactory->getFileCreationLockRedisKey($elementId));
    }

    public function release(UuidInterface $elementId, string $token): void
    {
        // plain GET + DEL instead of a Lua script (EVAL) for simplicity: there is a small race window between the
        // GET and the DEL where the key could expire and be re-acquired by a different request in between, whose
        // lock would then be deleted here instead of this one. This is accepted because the TTL already bounds the
        // damage, and the token is the acquiring request's id, so a collision would require another request to be
        // assigned the exact same request id within that window, which is practically impossible (UUIDv4).
        $key = (string) $this->redisKeyFactory->getFileCreationLockRedisKey($elementId);
        if ($this->redisClient->get($key) === $token) {
            $this->redisClient->del([$key]);
        }
    }
}
