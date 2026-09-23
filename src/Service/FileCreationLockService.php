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
 * The lock is a Redis key holding a random token; it expires on its own so that a crashed request can not block the
 * element forever, and is only released by its owner (compare-and-delete).
 */
class FileCreationLockService
{
    // a single request upload may take 15 minutes at most (`read_body` in the Caddyfile)
    public const int TTL_IN_MILLISECONDS = 900000;

    private const string RELEASE_SCRIPT = 'if redis.call("get", KEYS[1]) == ARGV[1] then return redis.call("del", KEYS[1]) else return 0 end';

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyFactory,
    ) {
    }

    /**
     * @return string|null lock token which has to be passed to {@see release()}, null if the element is already locked
     */
    public function acquire(UuidInterface $elementId): ?string
    {
        $token = bin2hex(random_bytes(16));
        $result = $this->redisClient->set((string) $this->redisKeyFactory->getFileCreationLockRedisKey($elementId), $token, 'PX', self::TTL_IN_MILLISECONDS, 'NX');

        return null === $result ? null : $token;
    }

    public function isLocked(UuidInterface $elementId): bool
    {
        return 0 !== (int) $this->redisClient->exists((string) $this->redisKeyFactory->getFileCreationLockRedisKey($elementId));
    }

    public function release(UuidInterface $elementId, string $token): void
    {
        $this->redisClient->eval(self::RELEASE_SCRIPT, 1, (string) $this->redisKeyFactory->getFileCreationLockRedisKey($elementId), $token);
    }
}
