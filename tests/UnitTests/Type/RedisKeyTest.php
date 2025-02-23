<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Type;

use App\Type\RedisKey;
use App\Type\RedisPrefixType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(RedisKey::class)]
class RedisKeyTest extends TestCase
{
    public function testRedisKey(): void
    {
        $redisKey = new RedisKey(RedisPrefixType::ETAG_ELEMENT, 'someString');
        $this->assertSame('etag:element:someString', $redisKey->getRedisKey());
        $this->assertSame('etag:element:someString', (string) $redisKey);
    }
}
