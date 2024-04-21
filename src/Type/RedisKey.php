<?php

declare(strict_types=1);

namespace App\Type;

use Stringable;

class RedisKey implements Stringable
{
    private string $redisKey;

    public function __construct(
        RedisPrefixType $prefix,
        string $value
    ) {
        $this->redisKey = sprintf('%s%s', $prefix->value, $value);
    }

    public function getRedisKey(): string
    {
        return $this->redisKey;
    }

    public function __toString()
    {
        return $this->redisKey;
    }
}
