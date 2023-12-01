<?php

namespace App\Type;

enum TokenStateType: string
{
    case ACTIVE = 'ACTIVE';
    case REVOKED = 'REVOKED';
}
