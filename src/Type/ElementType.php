<?php

declare(strict_types=1);

namespace App\Type;

enum ElementType: string
{
    case NODE = 'node';
    case RELATION = 'relation';
}
