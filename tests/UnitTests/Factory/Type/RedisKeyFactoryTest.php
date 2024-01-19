<?php

namespace App\tests\UnitTests\Factory\Type;

use App\Factory\Type\RedisKeyFactory;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class RedisKeyFactoryTest extends TestCase
{
    public function testGetTokenRedisKey(): void
    {
        $redisKeyTypeFactory = new RedisKeyFactory();

        $tokenRedisKey = $redisKeyTypeFactory->getTokenRedisKey();
        $this->assertSame('token:', (string) $tokenRedisKey);
    }

    public function testGetEtagElementRedisKey(): void
    {
        $redisKeyTypeFactory = new RedisKeyFactory();

        $etagElementRedisKey = $redisKeyTypeFactory->getEtagElementRedisKey(Uuid::fromString('644ddc69-de1e-4636-b3b6-06c8b001fbc1'));
        $this->assertSame('etag:element:644ddc69-de1e-4636-b3b6-06c8b001fbc1', (string) $etagElementRedisKey);
    }

    public function testGetEtagChildrenCollectionRedisKey(): void
    {
        $redisKeyTypeFactory = new RedisKeyFactory();

        $etagChildrenCollectionRedisKey = $redisKeyTypeFactory->getEtagChildrenCollectionRedisKey(Uuid::fromString('644ddc69-de1e-4636-b3b6-06c8b001fbc1'));
        $this->assertSame('etag:children:644ddc69-de1e-4636-b3b6-06c8b001fbc1', (string) $etagChildrenCollectionRedisKey);
    }

    public function testGetEtagParentsCollectionRedisKey(): void
    {
        $redisKeyTypeFactory = new RedisKeyFactory();

        $etagParentsCollectionRedisKey = $redisKeyTypeFactory->getEtagParentsCollectionRedisKey(Uuid::fromString('644ddc69-de1e-4636-b3b6-06c8b001fbc1'));
        $this->assertSame('etag:parents:644ddc69-de1e-4636-b3b6-06c8b001fbc1', (string) $etagParentsCollectionRedisKey);
    }

    public function testGetEtagRelatedCollectionRedisKey(): void
    {
        $redisKeyTypeFactory = new RedisKeyFactory();

        $etagRelatedCollectionRedisKey = $redisKeyTypeFactory->getEtagRelatedCollectionRedisKey(Uuid::fromString('644ddc69-de1e-4636-b3b6-06c8b001fbc1'));
        $this->assertSame('etag:related:644ddc69-de1e-4636-b3b6-06c8b001fbc1', (string) $etagRelatedCollectionRedisKey);
    }

    public function testGetEtagIndexCollectionRedisKey(): void
    {
        $redisKeyTypeFactory = new RedisKeyFactory();

        $etagIndexCollectionRedisKey = $redisKeyTypeFactory->getEtagIndexCollectionRedisKey(Uuid::fromString('644ddc69-de1e-4636-b3b6-06c8b001fbc1'));
        $this->assertSame('etag:index:644ddc69-de1e-4636-b3b6-06c8b001fbc1', (string) $etagIndexCollectionRedisKey);
    }
}
