<?php

declare(strict_types=1);

namespace App\Response;

use App\Contract\EtagCapableResponseInterface;
use App\Type\Etag;

class ElementResponse extends JsonResponse implements EtagCapableResponseInterface
{
    public function setEtagFromEtagInstance(Etag $etag): static
    {
        return parent::setEtag((string) $etag);
    }
}
