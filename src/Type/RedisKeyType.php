<?php

namespace App\Type;

use Stringable;

class RedisKeyType implements Stringable
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
