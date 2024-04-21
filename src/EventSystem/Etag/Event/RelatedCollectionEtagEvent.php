<?php

declare(strict_types=1);

namespace App\EventSystem\Etag\Event;

use App\Contract\EventInterface;
use App\Trait\StoppableEventTrait;
use App\Type\Etag;
use Ramsey\Uuid\UuidInterface;

class RelatedCollectionEtagEvent implements EventInterface
{
    use StoppableEventTrait;

    private ?Etag $etag = null;

    public function __construct(
        private UuidInterface $centerUuid
    ) {
    }

    public function getEtag(): ?Etag
    {
        return $this->etag;
    }

    public function setEtag(?Etag $etag): self
    {
        $this->etag = $etag;

        return $this;
    }

    public function getCenterUuid(): UuidInterface
    {
        return $this->centerUuid;
    }
}
