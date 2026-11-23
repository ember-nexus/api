<?php

declare(strict_types=1);

namespace App\Helper;

class Regex
{
    /**
     * Base62sort (canonical) or base16 representation (with or without dashes) of a UUIDsv2a7; the exact validation,
     * e.g. type and version, is done when parsing the value.
     */
    public const string UUID_CONTROLLER = '[0-9A-Za-z]{37}|[0-9a-fA-F]{8}\-[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}\-[0-9a-fA-F]{14}|[0-9a-fA-F]{54}';
    public const string UUID = '/^(?:'.self::UUID_CONTROLLER.')$/';

    /**
     * Node labels and relation types. Letters, digits and underscores only, so a type is a valid, unquoted identifier
     * in every query and can be stored in the id -> type lookup file (which limits the stored name to 255 bytes) and as a MongoDB collection name (namespace limit 255 bytes,
     * with database prefix).
     */
    public const string TYPE = '/^[A-Za-z][A-Za-z0-9_]{0,127}$/D';
}
