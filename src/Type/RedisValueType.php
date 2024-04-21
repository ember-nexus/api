<?php

declare(strict_types=1);

namespace App\Type;

enum RedisValueType: string
{
    case NULL = 'NULL_VALUE';
}
