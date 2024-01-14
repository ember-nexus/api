<?php

namespace App\EventSystem\Etag\Event;

use App\Contract\EventInterface;
use App\Trait\StoppableEventTrait;
use Ramsey\Uuid\UuidInterface;

class ParentsCollectionEtagEvent implements EventInterface
{
    use StoppableEventTrait;

    private ?string $etag = null;

    public function __construct(
        private UuidInterface $childUuid
    ) {
    }

    public function getEtag(): ?string
    {
        return $this->etag;
    }

    public function setEtag(?string $etag): self
    {
        $this->etag = $etag;

        return $this;
    }

    public function getChildUuid(): UuidInterface
    {
        return $this->childUuid;
    }
}
