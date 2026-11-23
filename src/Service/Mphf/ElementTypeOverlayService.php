<?php

declare(strict_types=1);

namespace App\Service\Mphf;

use App\Factory\Type\RedisKeyFactory;
use App\Type\MphfOverlaySlot;
use Predis\Client as RedisClient;

/**
 * Redis overlay of the id -> type lookup for elements which are not part of the loaded `.mphf` file yet.
 *
 * Two hashes (parent keys) `mphf-overlay:A` and `mphf-overlay:B` map ids to type names. One of them is active and takes
 * all new writes, the other one is the previous one and is only read. Every build switches the active key before it
 * exports the elements and, after it was published, clears the then inactive key, as everything in it is part of the
 * published file. The overlay therefore never needs a lock.
 *
 * Redis must not evict these keys (`maxmemory-policy noeviction`), the overlay is load-bearing for correctness.
 */
class ElementTypeOverlayService
{
    // reads the pointer and writes in one atomic step, so a write can never land in a key that was just switched away
    private const string WRITE_SCRIPT = <<<'LUA'
        local slot = redis.call('GET', KEYS[1])
        local target = KEYS[2]
        if slot == 'B' then target = KEYS[3] end
        redis.call('HSET', target, ARGV[1], ARGV[2])
        -- the id also goes into the active element id filter, if there is one (see ElementIdFilterService)
        local filterSlot = redis.call('GET', KEYS[4])
        local filterKey = KEYS[5]
        if filterSlot == 'B' then filterKey = KEYS[6] end
        if redis.call('EXISTS', filterKey) == 1 then redis.pcall('BF.ADD', filterKey, ARGV[1]) end
        return target
        LUA;

    private const string SWITCH_SCRIPT = <<<'LUA'
        local slot = redis.call('GET', KEYS[1])
        local target = KEYS[2]
        if slot == 'B' then slot = 'A' else slot = 'B'; target = KEYS[3] end
        redis.call('SET', KEYS[1], slot)
        -- the key might still carry the expiry of an earlier build (see expireInactive()), it takes writes now
        redis.call('PERSIST', target)
        return slot
        LUA;

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyFactory,
    ) {
    }

    /**
     * Remembers the type of a new element, in the currently active key, and adds its id to the element id filter. Has
     * to be called before the database write; if the write then fails, the entry points at an id that doesn't exist,
     * which is harmless.
     */
    public function register(string $elementId, string $type): void
    {
        $this->redisClient->eval(
            self::WRITE_SCRIPT,
            6,
            (string) $this->redisKeyFactory->getMphfOverlayActiveRedisKey(),
            (string) $this->redisKeyFactory->getMphfOverlayRedisKey(MphfOverlaySlot::A),
            (string) $this->redisKeyFactory->getMphfOverlayRedisKey(MphfOverlaySlot::B),
            (string) $this->redisKeyFactory->getElementIdFilterActiveRedisKey(),
            (string) $this->redisKeyFactory->getElementIdFilterRedisKey(MphfOverlaySlot::A),
            (string) $this->redisKeyFactory->getElementIdFilterRedisKey(MphfOverlaySlot::B),
            $elementId,
            $type,
        );
    }

    /**
     * Type of the element from the active key, then the inactive key; null if neither knows it. One round trip.
     */
    public function lookup(string $elementId): ?string
    {
        $keyA = (string) $this->redisKeyFactory->getMphfOverlayRedisKey(MphfOverlaySlot::A);
        $keyB = (string) $this->redisKeyFactory->getMphfOverlayRedisKey(MphfOverlaySlot::B);
        $activeKey = (string) $this->redisKeyFactory->getMphfOverlayActiveRedisKey();
        /** @var array{0: string|null, 1: string|null, 2: string|null} $replies */
        $replies = $this->redisClient->pipeline(static function (\Predis\ClientContextInterface $pipe) use ($keyA, $keyB, $activeKey, $elementId): void {
            $pipe->hget($keyA, $elementId);
            $pipe->hget($keyB, $elementId);
            $pipe->get($activeKey);
        });
        [$a, $b, $active] = $replies;
        $first = 'B' === $active ? $b : $a;
        $second = 'B' === $active ? $a : $b;
        $type = $first ?? $second;
        if (null !== $type && '' !== $type) {
            return $type;
        }

        return null;
    }

    /**
     * Types of many elements, same rules as {@see lookup()} but in one round trip for all of them.
     *
     * @param list<string> $elementIds
     *
     * @return array<string, string> type by element id, ids which neither key knows are missing
     */
    public function lookupMany(array $elementIds): array
    {
        if ([] === $elementIds) {
            return [];
        }
        $keyA = (string) $this->redisKeyFactory->getMphfOverlayRedisKey(MphfOverlaySlot::A);
        $keyB = (string) $this->redisKeyFactory->getMphfOverlayRedisKey(MphfOverlaySlot::B);
        $activeKey = (string) $this->redisKeyFactory->getMphfOverlayActiveRedisKey();
        /** @var array{0: list<string|null>, 1: list<string|null>, 2: string|null} $replies */
        $replies = $this->redisClient->pipeline(static function (\Predis\ClientContextInterface $pipe) use ($keyA, $keyB, $activeKey, $elementIds): void {
            $pipe->hmget($keyA, $elementIds);
            $pipe->hmget($keyB, $elementIds);
            $pipe->get($activeKey);
        });
        [$a, $b, $active] = $replies;
        $first = 'B' === $active ? $b : $a;
        $second = 'B' === $active ? $a : $b;
        $types = [];
        foreach ($elementIds as $index => $elementId) {
            $type = $first[$index] ?? $second[$index] ?? null;
            if (null !== $type && '' !== $type) {
                $types[$elementId] = $type;
            }
        }

        return $types;
    }

    /**
     * Flips the active key and returns the slot which takes writes from now on. The previously active key keeps its
     * entries and becomes the inactive one.
     */
    public function switchActive(): MphfOverlaySlot
    {
        /** @psalm-suppress MixedAssignment */
        $slot = $this->redisClient->eval(
            self::SWITCH_SCRIPT,
            3,
            (string) $this->redisKeyFactory->getMphfOverlayActiveRedisKey(),
            (string) $this->redisKeyFactory->getMphfOverlayRedisKey(MphfOverlaySlot::A),
            (string) $this->redisKeyFactory->getMphfOverlayRedisKey(MphfOverlaySlot::B),
        );

        return MphfOverlaySlot::from(is_string($slot) ? $slot : 'A');
    }

    public function getActiveSlot(): MphfOverlaySlot
    {
        return 'B' === $this->redisClient->get((string) $this->redisKeyFactory->getMphfOverlayActiveRedisKey()) ? MphfOverlaySlot::B : MphfOverlaySlot::A;
    }

    /**
     * Deletes the inactive key (non blocking UNLINK). Only call this after the build which covers its entries was
     * published.
     */
    public function clearInactive(): void
    {
        $inactive = $this->getActiveSlot()->other();
        $this->redisClient->unlink([(string) $this->redisKeyFactory->getMphfOverlayRedisKey($inactive)]);
    }

    /**
     * Lets Redis delete the inactive key after the given time, so the build does not have to wait: until then
     * processes which still work with the previous table keep finding recently created elements. The next switch
     * removes the expiry again if the key becomes the active one.
     */
    public function expireInactive(int $seconds): void
    {
        $inactive = $this->getActiveSlot()->other();
        $this->redisClient->expire((string) $this->redisKeyFactory->getMphfOverlayRedisKey($inactive), $seconds);
    }

    /**
     * Deletes both keys and the pointer (database reset, backup load).
     */
    public function clearAll(): void
    {
        $this->redisClient->unlink([
            (string) $this->redisKeyFactory->getMphfOverlayRedisKey(MphfOverlaySlot::A),
            (string) $this->redisKeyFactory->getMphfOverlayRedisKey(MphfOverlaySlot::B),
            (string) $this->redisKeyFactory->getMphfOverlayActiveRedisKey(),
        ]);
    }
}
