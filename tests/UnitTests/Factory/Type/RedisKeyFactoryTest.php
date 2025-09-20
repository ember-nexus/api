<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Type;

use App\Factory\Type\RedisKeyFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(RedisKeyFactory::class)]
class RedisKeyFactoryTest extends TestCase
{
    public function testGetTokenRedisKey(): void
    {
        $redisKeyTypeFactory = new RedisKeyFactory();

        $tokenRedisKey = $redisKeyTypeFactory->getTokenRedisKey();
        $this->assertSame('token:', (string) $tokenRedisKey);
    }

    public function testGetValidatedQueryCypherPathSubsetRedisKey(): void
    {
        $redisKeyTypeFactory = new RedisKeyFactory();

        $query = 'MATCH path = ((:Type)) RETURN path LIMIT 10';

        $getValidatedQueryCypherPathSubsetRedisKey = $redisKeyTypeFactory->getValidatedQueryCypherPathSubsetRedisKey($query);
        $this->assertSame('validated-query:cypher-path-subset:132920224a38db1b499b5280b5714d895d58d111', (string) $getValidatedQueryCypherPathSubsetRedisKey);
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
