<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Type;

use App\Type\RedisKey;
use App\Type\RedisPrefixType;
use PHPUnit\Framework\TestCase;

class RedisKeyTest extends TestCase
{
    public function testRedisKey(): void
    {
        $redisKey = new RedisKey(RedisPrefixType::ETAG_ELEMENT, 'someString');
        $this->assertSame('etag:element:someString', $redisKey->getRedisKey());
        $this->assertSame('etag:element:someString', (string) $redisKey);
    }
}
