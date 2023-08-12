<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use App\Type\FragmentGroup;
use Psr\EventDispatcher\EventDispatcherInterface;

class ElementFragmentizeService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function fragmentize(NodeElementInterface|RelationElementInterface $element): FragmentGroup
    {
        if ($element instanceof NodeElementInterface) {
            $event = new NodeElementFragmentizeEvent($element);
        } else {
            $event = new RelationElementFragmentizeEvent($element);
        }
        $this->eventDispatcher->dispatch($event);

        return $event->getAsFragmentGroup();
    }
}
