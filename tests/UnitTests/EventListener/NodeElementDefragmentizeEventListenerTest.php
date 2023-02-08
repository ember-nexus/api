<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventListener;

use App\Event\NodeElementDefragmentizeEvent;
use App\EventListener\NodeElementDefragmentizeEventListener;
use PHPUnit\Framework\TestCase;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\MongoDataStructures\Type\Document;

class NodeElementDefragmentizeEventListenerTest extends TestCase
{
    public function testNodeElementDefragmentizeEvent(): void
    {
        $cypherFragment = (new Node())
            ->addLabel('Node')
            ->addProperty('id', '78f61245-8cfd-4039-ac7e-61407fa7e969')
            ->addIdentifier('id');
        $documentFragment = (new Document())
            ->setCollection('Node')
            ->setIdentifier('78f61245-8cfd-4039-ac7e-61407fa7e969');

        $event = new NodeElementDefragmentizeEvent($cypherFragment, $documentFragment);
        $listener = new NodeElementDefragmentizeEventListener();
        $listener->onNodeElementDefragmentizeEvent($event);
        $element = $event->getNodeElement();

        $this->assertSame('Node', $element->getLabel());
        $this->assertSame('78f61245-8cfd-4039-ac7e-61407fa7e969', $element->getIdentifier()->toString());
    }
}
