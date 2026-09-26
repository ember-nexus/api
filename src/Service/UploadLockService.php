<?php

declare(strict_types=1);

namespace App\Service;

use Predis\Client as RedisClient;
use Ramsey\Uuid\UuidInterface;

/**
 * Per-upload mutual exclusion, so that concurrent appends or deletions of the same upload cannot corrupt it
 * (draft-ietf-httpbis-resumable-upload, "The server MUST take measures to prevent race conditions [...]").
 *
 * The lock is a Redis key holding a random token; it expires on its own so that a crashed request cannot block
 * the upload forever, and is only released by its owner (compare-and-delete).
 */
class UploadLockService
{
    public const string KEY_PREFIX = 'upload-lock:';
    public const int TTL_IN_MILLISECONDS = 900000;

    private const string RELEASE_SCRIPT = 'if redis.call("get", KEYS[1]) == ARGV[1] then return redis.call("del", KEYS[1]) else return 0 end';

    public function __construct(
        private RedisClient $redisClient,
    ) {
    }

    /**
     * @return string|null lock token which has to be passed to {@see release()}, null if the upload is already locked
     */
    public function acquire(UuidInterface $uploadId): ?string
    {
        $token = bin2hex(random_bytes(16));
        $result = $this->redisClient->set(self::KEY_PREFIX.$uploadId->toString(), $token, 'PX', self::TTL_IN_MILLISECONDS, 'NX');

        return null === $result ? null : $token;
    }

    public function release(UuidInterface $uploadId, string $token): void
    {
        $this->redisClient->eval(self::RELEASE_SCRIPT, 1, self::KEY_PREFIX.$uploadId->toString(), $token);
    }
}
