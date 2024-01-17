<?php

namespace App\tests\UnitTests\EventSystem\ElementFragmentize\Event;

use App\EventSystem\ElementFragmentize\Event\RelationElementFragmentizeEvent;
use App\Type\RelationElement;
use PHPUnit\Framework\TestCase;
use Syndesi\CypherDataStructures\Type\Relation;
use Syndesi\ElasticDataStructures\Type\Document as ElasticDocument;
use Syndesi\MongoDataStructures\Type\Document as MongoDocument;

class RelationElementFragmentizeEventTest extends TestCase
{
    public function testRelationElementFragmentizeEvent(): void
    {
        $relationElement = new RelationElement();
        $cypherFragment = new Relation();
        $mongoFragment = new MongoDocument();
        $elasticFragment = new ElasticDocument();
        $fileFragment = null;

        $relationElementFragmentizeEvent = new RelationElementFragmentizeEvent(
            $relationElement,
            $cypherFragment,
            $mongoFragment,
            $elasticFragment,
            $fileFragment
        );

        $this->assertSame($relationElement, $relationElementFragmentizeEvent->getRelationElement());
        $this->assertSame($cypherFragment, $relationElementFragmentizeEvent->getCypherFragment());
        $this->assertSame($mongoFragment, $relationElementFragmentizeEvent->getMongoFragment());
        $this->assertSame($elasticFragment, $relationElementFragmentizeEvent->getElasticFragment());
        $this->assertSame($fileFragment, $relationElementFragmentizeEvent->getFileFragment());
    }
}
