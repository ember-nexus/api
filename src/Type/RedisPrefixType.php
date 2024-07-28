<?php

declare(strict_types=1);

namespace App\Type;

enum RedisPrefixType: string
{
    case TOKEN = 'token:';

    case ETAG_ELEMENT = 'etag:element:';
    case ETAG_CHILDREN_COLLECTION = 'etag:children:';
    case ETAG_PARENTS_COLLECTION = 'etag:parents:';
    case ETAG_RELATED_COLLECTION = 'etag:related:';
    case ETAG_INDEX_COLLECTION = 'etag:index:';
    case LOCK_FILE_UPLOAD_CHECK = 'lock:file-upload-check:';
}
