<?php

namespace App\tests\UnitTests\EventSystem\ElementDefragmentize\Event;

use App\EventSystem\ElementDefragmentize\Event\NodeElementDefragmentizeEvent;
use App\Type\NodeElement;
use PHPUnit\Framework\TestCase;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\MongoDataStructures\Type\Document;

class NodeElementDefragmentizeEventTest extends TestCase
{
    public function testNodeElementDefragmentizeEvent(): void
    {
        $nodeElement = new NodeElement();
        $cypherFragment = new Node();
        $documentFragment = new Document();
        $fileFragment = null;
        $nodeElementDefragmentizeEvent = new NodeElementDefragmentizeEvent($nodeElement, $cypherFragment, $documentFragment, $fileFragment);

        $this->assertSame($nodeElement, $nodeElementDefragmentizeEvent->getNodeElement());

        $this->assertSame($cypherFragment, $nodeElementDefragmentizeEvent->getCypherFragment());
        $this->assertSame($documentFragment, $nodeElementDefragmentizeEvent->getDocumentFragment());
        $this->assertSame($fileFragment, $nodeElementDefragmentizeEvent->getFileFragment());
    }
}
