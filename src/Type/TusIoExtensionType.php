<?php

declare(strict_types=1);

namespace App\Type;

enum TusIoExtensionType: string
{
    case CREATION_WITH_UPLOAD = 'creation-with-upload';
    case CREATION_DEFER_LENGTH = 'creation-defer-length';
    case EXPIRATION = 'expiration';
    case CHECKSUM = 'checksum';
    case CHECKSUM_TRAILER = 'checksum-trailer';
    case TERMINATION = 'termination';
    case CONCATENATION = 'concatenation';
}
