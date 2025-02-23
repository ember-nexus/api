<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory;

use App\Factory\RedisFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(RedisFactory::class)]
class RedisFactoryTest extends TestCase
{
    public function testRedisFactory(): void
    {
        $redisFactory = new RedisFactory(
            'some auth'
        );

        $this->assertTrue(method_exists($redisFactory, 'createRedis'));
    }
}
