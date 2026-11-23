<?php

declare(strict_types=1);

namespace App\Type;

enum MaintenanceLockType: string
{
    /** Held by `mphf:build`. */
    case MPHF = 'mphf';
    /** Held by `index:ensure`. */
    case DATABASE_INDICES = 'database-indices';
    /** Held by `backup:load` and `database:drop`. */
    case DATABASE = 'database';
}
