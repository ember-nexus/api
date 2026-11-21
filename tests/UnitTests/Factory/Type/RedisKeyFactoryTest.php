<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Type;

use App\Factory\Type\RedisKeyFactory;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

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

        $etagElementRedisKey = $redisKeyTypeFactory->getEtagElementRedisKey(Uuid::fromString('02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N'));
        $this->assertSame('etag:element:02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N', (string) $etagElementRedisKey);
    }

    public function testGetEtagChildrenCollectionRedisKey(): void
    {
        $redisKeyTypeFactory = new RedisKeyFactory();

        $etagChildrenCollectionRedisKey = $redisKeyTypeFactory->getEtagChildrenCollectionRedisKey(Uuid::fromString('02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N'));
        $this->assertSame('etag:children:02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N', (string) $etagChildrenCollectionRedisKey);
    }

    public function testGetEtagParentsCollectionRedisKey(): void
    {
        $redisKeyTypeFactory = new RedisKeyFactory();

        $etagParentsCollectionRedisKey = $redisKeyTypeFactory->getEtagParentsCollectionRedisKey(Uuid::fromString('02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N'));
        $this->assertSame('etag:parents:02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N', (string) $etagParentsCollectionRedisKey);
    }

    public function testGetEtagRelatedCollectionRedisKey(): void
    {
        $redisKeyTypeFactory = new RedisKeyFactory();

        $etagRelatedCollectionRedisKey = $redisKeyTypeFactory->getEtagRelatedCollectionRedisKey(Uuid::fromString('02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N'));
        $this->assertSame('etag:related:02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N', (string) $etagRelatedCollectionRedisKey);
    }

    public function testGetEtagIndexCollectionRedisKey(): void
    {
        $redisKeyTypeFactory = new RedisKeyFactory();

        $etagIndexCollectionRedisKey = $redisKeyTypeFactory->getEtagIndexCollectionRedisKey(Uuid::fromString('02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N'));
        $this->assertSame('etag:index:02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N', (string) $etagIndexCollectionRedisKey);
    }

    public function testGetEtagFileRedisKey(): void
    {
        $redisKeyTypeFactory = new RedisKeyFactory();

        $etagFileRedisKey = $redisKeyTypeFactory->getEtagFileRedisKey(Uuid::fromString('02IiNr0G8viOVKlKpYXx7Tc0KnSVHyqcRPTog'));
        $this->assertSame('etag:file:02IiNr0G8viOVKlKpYXx7Tc0KnSVHyqcRPTog', (string) $etagFileRedisKey);
    }

    public function testGetUploadLockRedisKey(): void
    {
        $redisKey = (new RedisKeyFactory())->getUploadLockRedisKey(Uuid::fromString('02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N'));
        $this->assertSame('lock:upload:02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N', (string) $redisKey);
    }

    public function testGetFileCreationLockRedisKey(): void
    {
        $redisKey = (new RedisKeyFactory())->getFileCreationLockRedisKey(Uuid::fromString('02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N'));
        $this->assertSame('lock:file-creation:02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N', (string) $redisKey);
    }

    public function testGetElementRequestLockRedisKey(): void
    {
        $redisKey = (new RedisKeyFactory())->getElementRequestLockRedisKey(Uuid::fromString('02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N'));
        $this->assertSame('lock:element-request:02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N', (string) $redisKey);
    }

    public function testGetCronDeleteExpiredUploadRedisKey(): void
    {
        $redisKey = (new RedisKeyFactory())->getCronDeleteExpiredUploadRedisKey('02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N');
        $this->assertSame('cron:delete-expired-upload:02IiNr0G8viOVKk81TSk4eavxImNQ69STIv6N', (string) $redisKey);
    }
}
