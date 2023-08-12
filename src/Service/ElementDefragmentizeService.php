<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\ElementDefragmentize\Event\NodeElementDefragmentizeEvent;
use App\EventSystem\ElementDefragmentize\Event\RelationElementDefragmentizeEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Syndesi\CypherDataStructures\Contract\NodeInterface;
use Syndesi\CypherDataStructures\Contract\RelationInterface;
use Syndesi\MongoDataStructures\Contract\DocumentInterface;

class ElementDefragmentizeService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function defragmentize(NodeInterface|RelationInterface $cypherFragment, ?DocumentInterface $documentFragment): NodeElementInterface|RelationElementInterface
    {
        if ($cypherFragment instanceof NodeInterface) {
            $event = new NodeElementDefragmentizeEvent($cypherFragment, $documentFragment);
            $this->eventDispatcher->dispatch($event);

            return $event->getNodeElement();
        }
        $event = new RelationElementDefragmentizeEvent($cypherFragment, $documentFragment);
        $this->eventDispatcher->dispatch($event);

        return $event->getRelationElement();
    }
}
