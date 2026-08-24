<?php

declare(strict_types=1);

namespace App\Type;

/**
 * A single, resolved, inclusive byte range (RFC 9110, Section 14.1.2), together with the total length of the
 * resource it was resolved against. Concatenation of multiple ranges within a single request ("multipart/byteranges")
 * is not supported.
 */
readonly class ByteRange
{
    public function __construct(
        private int $start,
        private int $end,
        private int $totalLength,
    ) {
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    public function getTotalLength(): int
    {
        return $this->totalLength;
    }

    public function getLength(): int
    {
        return $this->end - $this->start + 1;
    }
}
