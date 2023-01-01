<?php

declare(strict_types=1);

namespace App\Tests\EventListener\Neo4j;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Event\RawToElementEvent;
use App\EventListener\RawToElementEventListener;
use PHPUnit\Framework\TestCase;

class RawToElementEventListenerTest extends TestCase
{
    public function testNodeRawToElementEvent(): void
    {
        $rawData = [
            'type' => 'Node',
            'id' => '59303c75-316b-4a4f-b554-26bdaee91a0e',
            'someProperty' => 'some value',
        ];
        $event = new RawToElementEvent($rawData);
        $rawToElementListener = new RawToElementEventListener();
        $rawToElementListener->onRawToElementEvent($event);
        $element = $event->getElement();
        $this->assertInstanceOf(NodeElementInterface::class, $element);
        $this->assertSame('59303c75-316b-4a4f-b554-26bdaee91a0e', $element->getIdentifier()->toString());
        $this->assertSame('Node', $element->getLabel());
        $this->assertCount(1, $element->getProperties());
    }

    public function testRelationRawToElementEvent(): void
    {
        $rawData = [
            'type' => 'RELATION',
            'id' => '59303c75-316b-4a4f-b554-26bdaee91a0e',
            'startNode' => '813bd638-5465-4f3b-a15b-a32773c2e8a4',
            'endNode' => 'a3a6531f-d1d4-4c2b-a0f9-7f55e3d6c40c',
            'someProperty' => 'some value',
        ];
        $event = new RawToElementEvent($rawData);
        $rawToElementListener = new RawToElementEventListener();
        $rawToElementListener->onRawToElementEvent($event);
        $element = $event->getElement();
        $this->assertInstanceOf(RelationElementInterface::class, $element);
        $this->assertSame('59303c75-316b-4a4f-b554-26bdaee91a0e', $element->getIdentifier()->toString());
        $this->assertSame('813bd638-5465-4f3b-a15b-a32773c2e8a4', $element->getStartNode()->toString());
        $this->assertSame('a3a6531f-d1d4-4c2b-a0f9-7f55e3d6c40c', $element->getEndNode()->toString());
        $this->assertSame('RELATION', $element->getType());
        $this->assertCount(1, $element->getProperties());
    }
}
