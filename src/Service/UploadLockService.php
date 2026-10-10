<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Type\RedisKeyFactory;
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
    // a request may take 15 minutes at most (`read_body` in the Caddyfile), plus time for S3 and finalization
    public const int TTL_IN_MILLISECONDS = 1200000;

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyFactory,
    ) {
    }

    /**
     * @return string|null lock token which has to be passed to {@see release()}, null if the upload is already locked
     */
    public function acquire(UuidInterface $uploadId): ?string
    {
        $token = bin2hex(random_bytes(16));
        $result = $this->redisClient->set((string) $this->redisKeyFactory->getUploadLockRedisKey($uploadId), $token, 'PX', self::TTL_IN_MILLISECONDS, 'NX');

        return null === $result ? null : $token;
    }

    public function release(UuidInterface $uploadId, string $token): void
    {
        // plain GET + DEL instead of a Lua script (EVAL) for simplicity: there is a small race window between the
        // GET and the DEL where the key could expire and be re-acquired by a different request in between, whose
        // lock would then be deleted here instead of this one. This is accepted because the TTL already bounds the
        // damage, and the token is a random 128-bit value, so a collision within that window is practically
        // impossible.
        $key = (string) $this->redisKeyFactory->getUploadLockRedisKey($uploadId);
        if ($this->redisClient->get($key) === $token) {
            $this->redisClient->del([$key]);
        }
    }
}
