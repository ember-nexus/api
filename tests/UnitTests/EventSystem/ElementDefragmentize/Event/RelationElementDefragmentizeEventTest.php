<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementDefragmentize\Event;

use App\EventSystem\ElementDefragmentize\Event\RelationElementDefragmentizeEvent;
use App\Type\RelationElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Syndesi\CypherDataStructures\Type\Relation;
use Syndesi\MongoDataStructures\Type\Document;

#[Small]
#[CoversClass(RelationElementDefragmentizeEvent::class)]
class RelationElementDefragmentizeEventTest extends TestCase
{
    public function testRelationElementDefragmentizeEvent(): void
    {
        $relationElement = new RelationElement();
        $cypherFragment = new Relation();
        $documentFragment = new Document();
        $fileFragment = null;
        $relationElementDefragmentizeEvent = new RelationElementDefragmentizeEvent($relationElement, $cypherFragment, $documentFragment, $fileFragment);

        $this->assertSame($relationElement, $relationElementDefragmentizeEvent->getRelationElement());

        $this->assertSame($cypherFragment, $relationElementDefragmentizeEvent->getCypherFragment());
        $this->assertSame($documentFragment, $relationElementDefragmentizeEvent->getDocumentFragment());
        $this->assertSame($fileFragment, $relationElementDefragmentizeEvent->getFileFragment());
    }
}
