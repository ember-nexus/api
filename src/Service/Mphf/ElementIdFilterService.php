<?php

declare(strict_types=1);

namespace App\Service\Mphf;

use App\Factory\Type\RedisKeyFactory;
use App\Type\MphfOverlaySlot;
use Predis\Client as RedisClient;
use Throwable;

/**
 * Bloom filter of all element ids (Redis 8, or Redis with the RedisBloom module), which answers "this id definitely
 * does not exist" for nearly every new id, so that the duplicate id check of a create request needs no Neo4j query.
 *
 * A Bloom filter has false positives but no false negatives, so only a "no" is trusted; every other answer (maybe, or
 * the filter can't be used) is verified in the database. That is only true if the filter contains every element which
 * exists, which is why it is only used while it is complete:
 *
 * - Two filters, `element-id-filter:A` and `:B`, and a pointer `:active` to the one which takes new ids, like the
 *   overlay of the type lookup. New ids are added together with the overlay entry (see {@see ElementTypeOverlayService}),
 *   before the element is written, so an element is in the filter from the moment it can exist.
 * - A build ({@see MphfBuildService}) reserves the inactive filter, switches the pointer to it, and
 *   fills it with all existing ids. Only when it is filled, `:ready` is set to its name. Until then, and whenever `:ready`
 *   names a filter which is not the active one or which is missing, nothing is trusted.
 * - Deleted elements stay in the filter, which only costs a database check if their id is used again.
 *
 * If Redis can't do Bloom filters, or the filter is disabled, every call is a no-op and `mightExist()` answers null.
 */
class ElementIdFilterService
{
    public const int ADD_CHUNK_SIZE = 1000;

    // -1: the filter can't be trusted, 0: the id does not exist, 1: the id might exist
    private const string LOOKUP_SCRIPT = <<<'LUA'
        local active = redis.call('GET', KEYS[1])
        if active ~= 'B' then active = 'A' end
        if redis.call('GET', KEYS[2]) ~= active then return -1 end
        local key = KEYS[3]
        if active == 'B' then key = KEYS[4] end
        if redis.call('EXISTS', key) == 0 then return -1 end
        return redis.call('BF.EXISTS', key, ARGV[1])
        LUA;

    private const string PREPARE_SCRIPT = <<<'LUA'
        local active = redis.call('GET', KEYS[1])
        local slot = 'A'
        if active ~= 'B' then slot = 'B' end
        local key = KEYS[2]
        if slot == 'B' then key = KEYS[3] end
        redis.call('UNLINK', key)
        redis.call('BF.RESERVE', key, ARGV[1], ARGV[2])
        redis.call('DEL', KEYS[4])
        redis.call('SET', KEYS[1], slot)
        return slot
        LUA;

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyFactory,
        private bool $enabled,
        private float $errorRate,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return bool|null false: the id definitely does not exist, true: it might, null: the filter can't be used right now
     */
    public function mightExist(string $elementId): ?bool
    {
        if (!$this->enabled) {
            return null;
        }
        try {
            /** @psalm-suppress MixedAssignment */
            $answer = $this->redisClient->eval(
                self::LOOKUP_SCRIPT,
                4,
                (string) $this->redisKeyFactory->getElementIdFilterActiveRedisKey(),
                (string) $this->redisKeyFactory->getElementIdFilterReadyRedisKey(),
                (string) $this->redisKeyFactory->getElementIdFilterRedisKey(MphfOverlaySlot::A),
                (string) $this->redisKeyFactory->getElementIdFilterRedisKey(MphfOverlaySlot::B),
                $elementId,
            );
        } catch (Throwable) {
            return null;
        }
        if (0 === $answer) {
            return false;
        }

        return 1 === $answer ? true : null;
    }

    /**
     * Starts a build: reserves the inactive filter for about $capacity ids (it grows if there are more), makes it the
     * active one and withdraws the trust in the filter until {@see finishBuild()}.
     *
     * @throws Throwable if Redis can't create Bloom filters
     */
    public function prepareBuild(int $capacity): MphfOverlaySlot
    {
        /** @psalm-suppress MixedAssignment */
        $slot = $this->redisClient->eval(
            self::PREPARE_SCRIPT,
            4,
            (string) $this->redisKeyFactory->getElementIdFilterActiveRedisKey(),
            (string) $this->redisKeyFactory->getElementIdFilterRedisKey(MphfOverlaySlot::A),
            (string) $this->redisKeyFactory->getElementIdFilterRedisKey(MphfOverlaySlot::B),
            (string) $this->redisKeyFactory->getElementIdFilterReadyRedisKey(),
            (string) $this->errorRate,
            (string) max(1000, $capacity),
        );

        return MphfOverlaySlot::from(is_string($slot) ? $slot : 'A');
    }

    /**
     * @param string[] $elementIds
     */
    public function addMany(MphfOverlaySlot $slot, array $elementIds): void
    {
        $key = (string) $this->redisKeyFactory->getElementIdFilterRedisKey($slot);
        foreach (array_chunk($elementIds, self::ADD_CHUNK_SIZE) as $chunk) {
            $this->redisClient->executeRaw(['BF.MADD', $key, ...$chunk]);
        }
    }

    /**
     * Declares the filled filter complete, from now on its "no" is trusted, and drops the other one.
     */
    public function finishBuild(MphfOverlaySlot $slot): void
    {
        $this->redisClient->set((string) $this->redisKeyFactory->getElementIdFilterReadyRedisKey(), $slot->value);
        $this->redisClient->unlink([(string) $this->redisKeyFactory->getElementIdFilterRedisKey($slot->other())]);
    }

    /**
     * Deletes everything of the filter (database reset).
     */
    public function clear(): void
    {
        $this->redisClient->unlink([
            (string) $this->redisKeyFactory->getElementIdFilterRedisKey(MphfOverlaySlot::A),
            (string) $this->redisKeyFactory->getElementIdFilterRedisKey(MphfOverlaySlot::B),
            (string) $this->redisKeyFactory->getElementIdFilterActiveRedisKey(),
            (string) $this->redisKeyFactory->getElementIdFilterReadyRedisKey(),
        ]);
    }
}
