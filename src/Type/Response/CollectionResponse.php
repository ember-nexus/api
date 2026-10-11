<?php

declare(strict_types=1);

namespace App\Type\Response;

use App\Contract\EtagCapableResponseInterface;
use App\Type\Etag;

class CollectionResponse extends JsonResponse implements EtagCapableResponseInterface
{
    public function setEtagFromEtagInstance(Etag $etag): static
    {
        return parent::setEtag((string) $etag);
    }
}
