<?php

declare(strict_types=1);

namespace App\Type;

enum PermissionType: string
{
    case READ = 'READ';
    case WRITE = 'WRITE';
    case CREATE = 'CREATE';
    case DELETE = 'DELETE';

    case NODE_READ = 'NODE_READ';
    case NODE_WRITE = 'NODE_WRITE';
    case NODE_CREATE = 'NODE_CREATE';
    case NODE_DELETE = 'NODE_DELETE';

    case RELATION_READ = 'RELATION_READ';
    case RELATION_WRITE = 'RELATION_WRITE';
    case RELATION_CREATE = 'RELATION_CREATE';
    case RELATION_DELETE = 'RELATION_DELETE';

    // CREATE_COMMENT_PERMISSION_ON_POST
    // CREATE_IMAGE_PERMISSION_ON_COMMENT
    //

    case CHANGE_OWNER = 'CHANGE_OWNER';
    case CHANGE_PERMISSION = 'CHANGE_PERMISSION';
}
