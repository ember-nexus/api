<?php

declare(strict_types=1);

namespace App\Type;

enum TokenStateType: string
{
    case ACTIVE = 'ACTIVE';
    case REVOKED = 'REVOKED';
}
