<?php

declare(strict_types=1);

namespace App\Type;

enum AccessType: string
{
    case READ = 'READ';
    case CREATE = 'CREATE';
    case UPDATE = 'UPDATE';
    case DELETE = 'DELETE';
    case SEARCH = 'SEARCH';
}
