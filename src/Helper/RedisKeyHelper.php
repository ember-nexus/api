<?php

namespace App\Helper;

use App\Type\RedisPrefixType;
use Ramsey\Uuid\UuidInterface;

class RedisKeyHelper
{
    // todo implement
    public static function getTokenRedisKey(): string
    {
        return '';
    }

    public static function getEtagElementRedisKey(UuidInterface $elementUuid): string
    {
        return sprintf(
            '%s%s',
            RedisPrefixType::ETAG_ELEMENT->value,
            $elementUuid->toString()
        );
    }

    public static function getEtagChildrenCollectionRedisKey(UuidInterface $parentUuid): string
    {
        return sprintf(
            '%s%s',
            RedisPrefixType::ETAG_CHILDREN_COLLECTION->value,
            $parentUuid->toString()
        );
    }

    public static function getEtagParentsCollectionRedisKey(UuidInterface $childUuid): string
    {
        return sprintf(
            '%s%s',
            RedisPrefixType::ETAG_PARENTS_COLLECTION->value,
            $childUuid->toString()
        );
    }

    public static function getEtagRelatedCollectionRedisKey(UuidInterface $centerUuid): string
    {
        return sprintf(
            '%s%s',
            RedisPrefixType::ETAG_RELATED_COLLECTION->value,
            $centerUuid->toString()
        );
    }

    public static function getEtagIndexCollectionRedisKey(UuidInterface $userUuid): string
    {
        return sprintf(
            '%s%s',
            RedisPrefixType::ETAG_INDEX_COLLECTION->value,
            $userUuid->toString()
        );
    }
}
