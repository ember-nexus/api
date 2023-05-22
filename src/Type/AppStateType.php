<?php

declare(strict_types=1);

namespace App\Type;

enum AppStateType: string
{
    case DEFAULT = 'DEFAULT';
    case LOADING_BACKUP = 'LOADING_BACKUP';
}
