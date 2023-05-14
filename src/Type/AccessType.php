<?php

namespace App\Type;

enum AccessType: string
{
    case READ = 'READ';
    case CREATE = 'CREATE';
}
