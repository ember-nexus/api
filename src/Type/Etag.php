<?php

namespace App\Type;

use Stringable;

readonly class Etag implements Stringable
{
    public function __construct(
        private string $etag
    ) {
    }

    public function __toString()
    {
        return $this->etag;
    }

    public function getEtag(): string
    {
        return $this->etag;
    }
}
