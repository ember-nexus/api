<?php

declare(strict_types=1);

namespace App\Attribute;

use App\Type\EtagType;
use Attribute;

#[Attribute]
readonly class EndpointSupportsEtag
{
    public function __construct(
        private EtagType $etagType,
    ) {
    }

    public function getEtagType(): EtagType
    {
        return $this->etagType;
    }
}
