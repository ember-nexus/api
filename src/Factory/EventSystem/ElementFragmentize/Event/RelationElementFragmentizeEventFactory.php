<?php

declare(strict_types=1);

namespace App\Factory\EventSystem\ElementFragmentize\Event;

use App\Contract\RelationElementInterface;
use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use Syndesi\CypherDataStructures\Type\Relation;
use Syndesi\ElasticDataStructures\Type\Document as ElasticDocument;
use Syndesi\MongoDataStructures\Type\Document as MongoDocument;

class RelationElementFragmentizeEventFactory
{
    public function createRelationElementFragmentizeEvent(RelationElementInterface $relationElement): RelationElementFragmentizeEvent
    {
        return new RelationElementFragmentizeEvent(
            $relationElement,
            new Relation(),
            new MongoDocument(),
            new ElasticDocument(),
            null
        );
    }
}
