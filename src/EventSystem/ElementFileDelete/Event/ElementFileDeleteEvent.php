<?php

declare(strict_types=1);

namespace App\EventSystem\ElementFileDelete\Event;

use App\Contract\EventInterface;
use App\Contract\NodeElementInterface;
use App\Trait\StoppableEventTrait;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherDataStructures\Contract\NodeInterface;
use Syndesi\ElasticDataStructures\Contract\DocumentInterface as ElasticDocumentInterface;
use Syndesi\MongoDataStructures\Contract\DocumentInterface as MongoDocumentInterface;

class ElementFileDeleteEvent implements EventInterface
{
    use StoppableEventTrait;

    public function __construct(
        private UuidInterface $elementId
    ) {
    }

    public function getElementId(): UuidInterface
    {
        return $this->elementId;
    }

}
