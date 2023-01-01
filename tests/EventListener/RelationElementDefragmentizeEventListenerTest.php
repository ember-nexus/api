<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Neo4j;

use App\Event\RelationElementDefragmentizeEvent;
use App\EventListener\RelationElementDefragmentizeEventListener;
use PHPUnit\Framework\TestCase;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\CypherDataStructures\Type\Relation;
use Syndesi\MongoDataStructures\Type\Document;

class RelationElementDefragmentizeEventListenerTest extends TestCase
{
    public function testRelationElementDefragmentizeEvent(): void
    {
        $cypherFragment = (new Relation())
            ->setType('RELATION')
            ->addProperty('id', '78f61245-8cfd-4039-ac7e-61407fa7e969')
            ->addIdentifier('id')
            ->setStartNode(
                (new Node())
                    ->addLabel('SomeNode')
                    ->addProperty('id', '2f84b61f-e062-4006-8915-45c951d58b68')
                    ->addIdentifier('id')
            )
            ->setEndNode(
                (new Node())
                    ->addLabel('SomeNode')
                    ->addProperty('id', '74f354c2-8367-4e61-98da-2a032137af34')
                    ->addIdentifier('id')
            );
        $documentFragment = (new Document())
            ->setCollection('RELATION')
            ->setIdentifier('78f61245-8cfd-4039-ac7e-61407fa7e969');

        $event = new RelationElementDefragmentizeEvent($cypherFragment, $documentFragment);
        $listener = new RelationElementDefragmentizeEventListener();
        $listener->onRelationElementDefragmentizeEvent($event);
        $element = $event->getRelationElement();

        $this->assertSame('RELATION', $element->getType());
        $this->assertSame('78f61245-8cfd-4039-ac7e-61407fa7e969', $element->getIdentifier()->toString());
        $this->assertSame('2f84b61f-e062-4006-8915-45c951d58b68', $element->getStartNode()->toString());
        $this->assertSame('74f354c2-8367-4e61-98da-2a032137af34', $element->getEndNode()->toString());
    }
}
