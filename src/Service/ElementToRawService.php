<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\ElementToRaw\Event\ElementToRawEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class ElementToRawService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function elementToRaw(NodeElementInterface|RelationElementInterface $element): array
    {
        $event = new ElementToRawEvent($element);
        $this->eventDispatcher->dispatch($event);

        return $event->getRawData();
    }
}
