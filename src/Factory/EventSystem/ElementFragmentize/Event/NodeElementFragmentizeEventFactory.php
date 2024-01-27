<?php

namespace App\Factory\EventSystem\ElementFragmentize\Event;

use App\Contract\NodeElementInterface;
use App\EventSystem\ElementFragmentize\Event\NodeElementFragmentizeEvent;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\ElasticDataStructures\Type\Document as ElasticDocument;
use Syndesi\MongoDataStructures\Type\Document as MongoDocument;

class NodeElementFragmentizeEventFactory
{
    public function createNodeElementFragmentizeEvent(NodeElementInterface $nodeElement): NodeElementFragmentizeEvent
    {
        return new NodeElementFragmentizeEvent(
            $nodeElement,
            new Node(),
            new MongoDocument(),
            new ElasticDocument(),
            null
        );
    }
}
