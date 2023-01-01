<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Event\ElementToRawEvent;
use App\Event\NodeElementFragmentizeEvent;
use App\Event\RelationElementFragmentizeEvent;
use App\Type\FragmentGroup;
use Psr\EventDispatcher\EventDispatcherInterface;

class ElementToRawService {

    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ){}

    public function elementToRaw(NodeElementInterface|RelationElementInterface $element): array
    {
        $event = new ElementToRawEvent($element);
        $this->eventDispatcher->dispatch($event);
        return $event->getRawData();
    }

}