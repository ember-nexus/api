<?php

declare(strict_types=1);

namespace App\Helper;

class CypherHelper
{
    /**
     * Escapes a label or relationship type name for use inside backticks; labels can't be passed as parameters.
     * Only ever use names which come from the schema or the type lookup, never user input.
     */
    public static function escapeIdentifier(string $name): string
    {
        return '`'.str_replace('`', '``', $name).'`';
    }
}
