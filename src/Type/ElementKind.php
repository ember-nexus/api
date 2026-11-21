<?php

declare(strict_types=1);

namespace App\Type;

enum ElementKind: string
{
    case NODE = 'node';
    case RELATION = 'relation';
}
