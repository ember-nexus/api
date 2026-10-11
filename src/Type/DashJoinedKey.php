<?php

declare(strict_types=1);

namespace App\Type;

use Stringable;

/**
 * Multiple key segments joined by a dash; null or empty segments are omitted instead of leaving a stray dash
 * (e.g. an upload chunk key without a chunk id).
 */
readonly class DashJoinedKey implements Stringable
{
    private string $key;

    public function __construct(?string ...$segments)
    {
        $this->key = implode('-', array_filter(
            $segments,
            static fn (?string $segment): bool => null !== $segment && '' !== $segment
        ));
    }

    public function __toString(): string
    {
        return $this->key;
    }
}
