<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Event\ElementToRawEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class ElementToRawService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function elementToRaw(NodeElementInterface|RelationElementInterface $element): array
    {
        $event = new ElementToRawEvent($element);
        $this->eventDispatcher->dispatch($event);

        return $event->getRawData();
    }
}
