<?php

namespace App\EventSystem\Etag\Event;

use App\Contract\EventInterface;
use App\Trait\StoppableEventTrait;
use Ramsey\Uuid\UuidInterface;

class IndexCollectionEtagEvent implements EventInterface
{
    use StoppableEventTrait;

    private ?string $etag = null;

    public function __construct(
        private UuidInterface $userUuid
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

    public function getUserUuid(): UuidInterface
    {
        return $this->userUuid;
    }
}
