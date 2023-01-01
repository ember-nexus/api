<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Neo4j;

use App\Event\NodeElementDefragmentizeEvent;
use App\EventListener\GenericPropertyElementDefragmentizeEventListener;
use PHPUnit\Framework\TestCase;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\MongoDataStructures\Type\Document;

class GenericPropertyElementDefragmentizeEventListenerTest extends TestCase
{
    public function testOnNodeElementDefragmentizeEvent(): void
    {
        $cypherFragment = (new Node())
            ->addLabel('Node')
            ->addProperty('id', '78f61245-8cfd-4039-ac7e-61407fa7e969')
            ->addIdentifier('id')
            ->addProperty('name', 'cypher name')
            ->addProperty('integer', 1234);
        $documentFragment = (new Document())
            ->setCollection('RELATION')
            ->setIdentifier('78f61245-8cfd-4039-ac7e-61407fa7e969')
            ->addProperty('name', 'document name')
            ->addProperty('longString', 'long string');

        $event = new NodeElementDefragmentizeEvent($cypherFragment, $documentFragment);
        $listener = new GenericPropertyElementDefragmentizeEventListener();
        $listener->onNodeElementDefragmentizeEvent($event);
        $element = $event->getNodeElement();

        $this->assertSame('cypher name', $element->getProperty('name'));
        $this->assertSame(1234, $element->getProperty('integer'));
        $this->assertSame('long string', $element->getProperty('longString'));
    }
}
