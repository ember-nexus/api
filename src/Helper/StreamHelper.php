<?php

declare(strict_types=1);

namespace App\Helper;

use LogicException;

class StreamHelper
{
    /**
     * @param resource $stream
     */
    public static function getSize(mixed $stream): int
    {
        $size = \Safe\fstat($stream)['size'];
        if (!is_int($size)) {
            throw new LogicException(sprintf('Expected fstat() to report the stream size as int, got %s.', get_debug_type($size)));
        }

        return $size;
    }
}
