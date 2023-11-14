<?php

namespace App\Type;

enum TokenStateType: string
{
    case ACTIVE = 'ACTIVE';
    case REVOKED = 'REVOKED';



    case READ = 'READ';
    case CREATE = 'CREATE';
    case UPDATE = 'UPDATE';
    case DELETE = 'DELETE';
    case SEARCH = 'SEARCH';
}
