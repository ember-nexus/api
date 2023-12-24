<?php

declare(strict_types=1);

namespace App\Type;

enum RedisPrefixType: string
{
    // token:<tokenHash>
    case TOKEN = 'token:';

    // etag:<elementUuid>[/optionalSubresource]
    case ETAG = 'etag:';
}
