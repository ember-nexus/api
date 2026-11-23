<?php

declare(strict_types=1);

namespace App\Factory\Type;

use App\Type\MaintenanceLockType;
use App\Type\MphfOverlaySlot;
use App\Type\RedisKey;
use App\Type\RedisPrefixType;
use App\Type\Uuid;

class RedisKeyFactory
{
    // todo implement
    public function getTokenRedisKey(): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::TOKEN,
            ''
        );
    }

    /**
     * Holds the node labels and relation types of the database, see {@see \App\Service\GraphStructureService}.
     */
    public function getGraphStructureRedisKey(): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::GRAPH_STRUCTURE,
            ''
        );
    }

    public function getRewrittenQueryCypherPathSubsetRedisKey(string $cypherQuery): RedisKey
    {
        /** @psalm-suppress WeakHash */
        $hashedCypherQuery = sha1($cypherQuery);

        return new RedisKey(
            RedisPrefixType::REWRITTEN_QUERY_CYPHER_PATH_SUBSET,
            $hashedCypherQuery
        );
    }

    public function getValidatedQueryCypherPathSubsetRedisKey(string $cypherQuery): RedisKey
    {
        /** @psalm-suppress WeakHash */
        $hashedCypherQuery = sha1($cypherQuery);

        return new RedisKey(
            RedisPrefixType::VALIDATED_QUERY_CYPHER_PATH_SUBSET,
            $hashedCypherQuery
        );
    }

    public function getEtagElementRedisKey(Uuid $elementId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_ELEMENT,
            $elementId->toString()
        );
    }

    public function getEtagChildrenCollectionRedisKey(Uuid $parentId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_CHILDREN_COLLECTION,
            $parentId->toString()
        );
    }

    public function getEtagParentsCollectionRedisKey(Uuid $childId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_PARENTS_COLLECTION,
            $childId->toString()
        );
    }

    public function getEtagRelatedCollectionRedisKey(Uuid $centerId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_RELATED_COLLECTION,
            $centerId->toString()
        );
    }

    public function getEtagIndexCollectionRedisKey(Uuid $userId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_INDEX_COLLECTION,
            $userId->toString()
        );
    }

    public function getEtagFileRedisKey(Uuid $elementId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_FILE,
            $elementId->toString()
        );
    }

    public function getUploadLockRedisKey(Uuid $uploadId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::LOCK_UPLOAD,
            $uploadId->toString()
        );
    }

    public function getFileCreationLockRedisKey(Uuid $elementId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::LOCK_FILE_CREATION,
            $elementId->toString()
        );
    }

    public function getElementRequestLockRedisKey(Uuid $elementId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::LOCK_ELEMENT_REQUEST,
            $elementId->toString()
        );
    }

    public function getCronDeleteExpiredUploadRedisKey(string $uploadId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::CRON_DELETE_EXPIRED_UPLOAD,
            $uploadId
        );
    }

    /**
     * Holds the file name of the current MPHF build; absent means that no MPHF is available.
     */
    public function getMphfVersionRedisKey(): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::MPHF_VERSION,
            ''
        );
    }

    /**
     * One of the two parent keys of the overlay, a hash which maps element ids to their types. Which one takes new
     * writes is stored in {@see getMphfOverlayActiveRedisKey()}.
     */
    public function getMphfOverlayRedisKey(MphfOverlaySlot $slot): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::MPHF_OVERLAY,
            $slot->value
        );
    }

    /**
     * One of the two Bloom filters of element ids, see {@see \App\Service\Mphf\ElementIdFilterService}.
     */
    public function getElementIdFilterRedisKey(MphfOverlaySlot $slot): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ELEMENT_ID_FILTER,
            $slot->value
        );
    }

    /**
     * Names the slot of the element id filter which takes new ids.
     */
    public function getElementIdFilterActiveRedisKey(): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ELEMENT_ID_FILTER,
            'active'
        );
    }

    /**
     * Names the slot of the element id filter which is complete, i.e. contains every element which exists. Absent
     * while the filter is built, or if it was never built.
     */
    public function getElementIdFilterReadyRedisKey(): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ELEMENT_ID_FILTER,
            'ready'
        );
    }

    public function getMphfOverlayActiveRedisKey(): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::MPHF_OVERLAY,
            'active'
        );
    }

    public function getMaintenanceLockRedisKey(MaintenanceLockType $type): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::LOCK_MAINTENANCE,
            $type->value
        );
    }
}
