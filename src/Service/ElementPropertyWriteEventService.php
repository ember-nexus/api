<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Event\ElementPropertyWriteEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class ElementPropertyWriteEventService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function updateProperties(NodeElementInterface|RelationElementInterface $element, array $newProperties = []): void
    {
        $event = new ElementPropertyWriteEvent($element, $newProperties);
        $this->eventDispatcher->dispatch($event);

        foreach ($event->getProperties() as $key => $value) {
            $event->getElement()->addProperty($key, $value);
        }
    }
}
