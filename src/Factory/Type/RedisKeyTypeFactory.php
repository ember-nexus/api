<?php

namespace App\Factory\Type;

use App\Type\RedisKeyType;
use App\Type\RedisPrefixType;
use Ramsey\Uuid\UuidInterface;

class RedisKeyTypeFactory
{
    // todo implement
    public function getTokenRedisKey(): RedisKeyType
    {
        return new RedisKeyType(
            RedisPrefixType::TOKEN,
            ''
        );
    }

    public function getEtagElementRedisKey(UuidInterface $elementUuid): RedisKeyType
    {
        return new RedisKeyType(
            RedisPrefixType::ETAG_ELEMENT,
            $elementUuid->toString()
        );
    }

    public function getEtagChildrenCollectionRedisKey(UuidInterface $parentUuid): RedisKeyType
    {
        return new RedisKeyType(
            RedisPrefixType::ETAG_CHILDREN_COLLECTION,
            $parentUuid->toString()
        );
    }

    public function getEtagParentsCollectionRedisKey(UuidInterface $childUuid): RedisKeyType
    {
        return new RedisKeyType(
            RedisPrefixType::ETAG_PARENTS_COLLECTION,
            $childUuid->toString()
        );
    }

    public function getEtagRelatedCollectionRedisKey(UuidInterface $centerUuid): RedisKeyType
    {
        return new RedisKeyType(
            RedisPrefixType::ETAG_RELATED_COLLECTION,
            $centerUuid->toString()
        );
    }

    public function getEtagIndexCollectionRedisKey(UuidInterface $userUuid): RedisKeyType
    {
        return new RedisKeyType(
            RedisPrefixType::ETAG_INDEX_COLLECTION,
            $userUuid->toString()
        );
    }
}
