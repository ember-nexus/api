<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\ElementDefragmentize\Event\NodeElementDefragmentizeEvent;
use App\EventSystem\ElementDefragmentize\Event\RelationElementDefragmentizeEvent;
use App\Type\NodeElement;
use App\Type\RelationElement;
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

    public function defragmentize(
        NodeInterface|RelationInterface $cypherFragment,
        ?DocumentInterface $documentFragment,
        mixed $fileFragment
    ): NodeElementInterface|RelationElementInterface {
        if ($cypherFragment instanceof NodeInterface) {
            $event = new NodeElementDefragmentizeEvent(
                new NodeElement(),
                $cypherFragment,
                $documentFragment,
                $fileFragment
            );
            $this->eventDispatcher->dispatch($event);

            return $event->getNodeElement();
        }
        $event = new RelationElementDefragmentizeEvent(
            new RelationElement(),
            $cypherFragment,
            $documentFragment,
            $fileFragment
        );
        $this->eventDispatcher->dispatch($event);

        return $event->getRelationElement();
    }
}
