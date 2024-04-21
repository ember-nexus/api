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

    public function getEtagElementRedisKey(UuidInterface $elementUuid): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_ELEMENT,
            $elementUuid->toString()
        );
    }

    public function getEtagChildrenCollectionRedisKey(UuidInterface $parentUuid): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_CHILDREN_COLLECTION,
            $parentUuid->toString()
        );
    }

    public function getEtagParentsCollectionRedisKey(UuidInterface $childUuid): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_PARENTS_COLLECTION,
            $childUuid->toString()
        );
    }

    public function getEtagRelatedCollectionRedisKey(UuidInterface $centerUuid): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_RELATED_COLLECTION,
            $centerUuid->toString()
        );
    }

    public function getEtagIndexCollectionRedisKey(UuidInterface $userUuid): RedisKey
    {
        return new RedisKey(
            RedisPrefixType::ETAG_INDEX_COLLECTION,
            $userUuid->toString()
        );
    }
}
