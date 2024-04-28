<?php

declare(strict_types=1);

namespace App\Type;

enum TusIoHeaderType: string
{
    case UPLOAD_OFFSET = 'Upload-Offset';
    case UPLOAD_LENGTH = 'Upload-Length';
    case TUS_RESUMABLE = 'Tus-Resumable';
    case TUS_VERSION = 'Tus-Version';
    case TUS_EXTENSION = 'Tus-Extension';
    case TUS_MAX_SIZE = 'Tus-Max-Size';
    case X_HTTP_METHOD_OVERRIDE = 'X-HTTP-Method-Override';
    case UPLOAD_DEFER_LENGTH = 'Upload-Defer-Length';
    case UPLOAD_METADATA = 'Upload-Metadata';
    case UPLOAD_EXPIRES = 'Upload-Expires';
    case TUS_CHECKSUM_ALGORITHM = 'Tus-Checksum-Algorithm';
    case UPLOAD_CHECKSUM = 'Upload-Checksum';
}
