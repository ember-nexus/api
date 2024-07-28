<?php

declare(strict_types=1);

namespace App\Attribute;

use Attribute;

#[Attribute]
readonly class EndpointImplementsTusIo
{
    public function __construct()
    {
    }
}
