<?php

namespace App\EventSystem\Etag\Event;

use App\Contract\EventInterface;
use App\Trait\StoppableEventTrait;
use App\Type\Etag;
use Ramsey\Uuid\UuidInterface;

class ChildrenCollectionEtagEvent implements EventInterface
{
    use StoppableEventTrait;

    private ?Etag $etag = null;

    public function __construct(
        private UuidInterface $parentUuid
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

    public function getParentUuid(): UuidInterface
    {
        return $this->parentUuid;
    }
}
