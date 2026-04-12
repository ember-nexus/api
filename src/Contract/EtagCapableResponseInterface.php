<?php

declare(strict_types=1);

namespace App\Contract;

use App\Type\Etag;

interface EtagCapableResponseInterface
{
    public function setEtagFromEtagInstance(Etag $etag): static;
}
