<?php

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Event\NodeElementDefragmentizeEvent;
use App\Event\NodeElementFragmentizeEvent;
use App\Event\RelationElementDefragmentizeEvent;
use App\Event\RelationElementFragmentizeEvent;
use App\Type\FragmentGroup;
use Psr\EventDispatcher\EventDispatcherInterface;
use Syndesi\CypherDataStructures\Contract\NodeInterface;
use Syndesi\CypherDataStructures\Contract\RelationInterface;
use Syndesi\MongoDataStructures\Contract\DocumentInterface;

class ElementDefragmentizeService {

    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ){}

    public function defragmentize(NodeInterface|RelationInterface $cypherFragment, DocumentInterface $documentFragment): NodeElementInterface|RelationElementInterface
    {
        if ($cypherFragment instanceof NodeInterface) {
            $event = new NodeElementDefragmentizeEvent($cypherFragment, $documentFragment);
            $this->eventDispatcher->dispatch($event);
            return $event->getNodeElement();
        }
        if ($cypherFragment instanceof RelationInterface) {
            $event = new RelationElementDefragmentizeEvent($cypherFragment, $documentFragment);
            $this->eventDispatcher->dispatch($event);
            return $event->getRelationElement();
        }
    }

}