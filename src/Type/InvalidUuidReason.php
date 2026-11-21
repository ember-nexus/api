<?php

declare(strict_types=1);

namespace App\Type;

enum InvalidUuidReason: string
{
    case UNKNOWN_FORMAT = 'UnknownFormat';
    case LEGACY_UUID_V4 = 'LegacyUuidV4';
    case INVALID_CHARACTER = 'InvalidCharacter';
    case OVERFLOW = 'Overflow';
    case UNSUPPORTED_VARIANT = 'UnsupportedVariant';
    case INVALID_VERSION = 'InvalidVersion';
    case INVALID_VARIANT = 'InvalidVariant';
    case INVALID_LENGTH = 'InvalidLength';
    case FUTURE_TIMESTAMP = 'FutureTimestamp';
    case TIMESTAMP_OUT_OF_RANGE = 'TimestampOutOfRange';
}
