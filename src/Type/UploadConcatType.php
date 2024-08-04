<?php

declare(strict_types=1);

namespace App\Type;

enum UploadConcatType: string
{
    case PARTIAL = 'partial';
    case FINAL = 'final';
}
