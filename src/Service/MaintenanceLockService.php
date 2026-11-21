<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Type\RedisKeyFactory;
use App\Type\MaintenanceLock;
use App\Type\MaintenanceLockType;
use Predis\Client as RedisClient;

/**
 * Cross-process locks for long running maintenance work (`mphf:build`, `index:ensure`, `backup:load`,
 * `database:drop`): `SET <key> <token> NX EX <ttl>`. The auto-expiry makes sure that a crashed or killed process can
 * never block maintenance for good; release, refresh and ownership checks compare the token, so a process never
 * touches a lock somebody else claimed after its own one expired.
 */
class MaintenanceLockService
{
    private const string RELEASE_SCRIPT = "if redis.call('GET', KEYS[1]) == ARGV[1] then return redis.call('DEL', KEYS[1]) else return 0 end";
    private const string REFRESH_SCRIPT = "if redis.call('GET', KEYS[1]) == ARGV[1] then return redis.call('EXPIRE', KEYS[1], ARGV[2]) else return 0 end";

    /**
     * @var array<string, MaintenanceLock>
     */
    private array $held = [];

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyFactory,
        private int $maintenanceLockTtl,
    ) {
    }

    /**
     * @return MaintenanceLock|null null if the lock is held by somebody else
     */
    public function claim(MaintenanceLockType $type): ?MaintenanceLock
    {
        $token = bin2hex(random_bytes(16));
        $result = $this->redisClient->set($this->key($type), $token, 'EX', $this->maintenanceLockTtl, 'NX');
        if (null === $result) {
            return null;
        }
        $lock = new MaintenanceLock($type, $token);
        $this->held[$type->value] = $lock;

        return $lock;
    }

    /**
     * Compare-and-delete; returns false if the lock expired or belongs to somebody else by now.
     */
    public function release(MaintenanceLock $lock): bool
    {
        unset($this->held[$lock->getType()->value]);

        return 1 === $this->evaluate(self::RELEASE_SCRIPT, $this->key($lock->getType()), $lock->getToken());
    }

    /**
     * Resets the expiry to the full TTL, for locks of work which legitimately runs longer than the TTL.
     */
    public function refresh(MaintenanceLock $lock): bool
    {
        return 1 === $this->evaluate(self::REFRESH_SCRIPT, $this->key($lock->getType()), $lock->getToken(), (string) $this->maintenanceLockTtl);
    }

    public function isStillOwned(MaintenanceLock $lock): bool
    {
        return $this->redisClient->get($this->key($lock->getType())) === $lock->getToken();
    }

    /**
     * True if anybody holds the lock.
     */
    public function isHeld(MaintenanceLockType $type): bool
    {
        return (bool) $this->redisClient->exists($this->key($type));
    }

    /**
     * Releases every lock this process still holds; used by signal handlers (SIGINT, SIGTERM).
     */
    public function releaseAllHeld(): void
    {
        foreach ($this->held as $lock) {
            $this->release($lock);
        }
    }

    private function key(MaintenanceLockType $type): string
    {
        return (string) $this->redisKeyFactory->getMaintenanceLockRedisKey($type);
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    private function evaluate(string $script, string $key, string ...$arguments): int
    {
        $reply = $this->redisClient->eval($script, 1, $key, ...$arguments);

        return is_int($reply) ? $reply : 0;
    }
}
