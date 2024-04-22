<?php

declare(strict_types=1);

namespace App\Factory\Type;

use App\Type\RedisKey;
use App\Type\RedisPrefixType;
use Ramsey\Uuid\UuidInterface;

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

    public function getEtagElementRedisKey(UuidInterface $elementId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_ELEMENT,
            $elementId->toString()
        );
    }

    public function getEtagChildrenCollectionRedisKey(UuidInterface $parentId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_CHILDREN_COLLECTION,
            $parentId->toString()
        );
    }

    public function getEtagParentsCollectionRedisKey(UuidInterface $childId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_PARENTS_COLLECTION,
            $childId->toString()
        );
    }

    public function getEtagRelatedCollectionRedisKey(UuidInterface $centerId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_RELATED_COLLECTION,
            $centerId->toString()
        );
    }

    public function getEtagIndexCollectionRedisKey(UuidInterface $userId): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_INDEX_COLLECTION,
            $userId->toString()
        );
    }
}
