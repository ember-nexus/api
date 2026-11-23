<?php

declare(strict_types=1);

namespace App\Type;

/**
 * The two parent keys of the id -> type overlay. Exactly one of them is active (takes new writes), the other one is
 * the previous one and is only read.
 */
enum MphfOverlaySlot: string
{
    case A = 'A';
    case B = 'B';

    public function other(): self
    {
        return self::A === $this ? self::B : self::A;
    }
}
