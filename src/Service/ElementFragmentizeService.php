<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Event\NodeElementFragmentizeEvent;
use App\Event\RelationElementFragmentizeEvent;
use App\Type\FragmentGroup;
use Psr\EventDispatcher\EventDispatcherInterface;

class ElementFragmentizeService {

    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ){}

    public function fragmentize(NodeElementInterface|RelationElementInterface $element): FragmentGroup
    {
        $event = null;
        if ($element instanceof NodeElementInterface) {
            $event = new NodeElementFragmentizeEvent($element);
        }
        if ($element instanceof RelationElementInterface) {
            $event = new RelationElementFragmentizeEvent($element);
        }
        $this->eventDispatcher->dispatch($event);
        return $event->getAsFragmentGroup();
    }

}