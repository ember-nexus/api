<?php

declare(strict_types=1);

namespace App\Type;

use Stringable;

/**
 * A name and its extension, joined by a dot; an empty extension means the name has none at all, so no trailing
 * dot is added (e.g. 'Makefile').
 */
readonly class DottedName implements Stringable
{
    public function __construct(
        private string $name,
        private string $extension,
    ) {
    }

    public function __toString(): string
    {
        return '' === $this->extension ? $this->name : sprintf('%s.%s', $this->name, $this->extension);
    }
}
