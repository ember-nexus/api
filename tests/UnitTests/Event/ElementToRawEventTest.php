<?php

namespace App\Test\UnitTests\Event;

use App\Event\ElementToRawEvent;
use App\Type\NodeElement;
use App\Type\RelationElement;
use PHPUnit\Framework\TestCase;

class ElementToRawEventTest extends TestCase
{
    public function testElementToRawEvent(): void
    {
        $nodeElement = new NodeElement();
        $event = new ElementToRawEvent($nodeElement);

        $this->assertEmpty($event->getRawData());
        $event->setRawData(['new' => 'data']);
        $this->assertCount(1, $event->getRawData());
        $this->assertSame('data', $event->getRawData()['new']);

        $this->assertSame($nodeElement, $event->getElement());
        $otherNodeElement = new NodeElement();
        $event->setElement($otherNodeElement);
        $this->assertNotSame($nodeElement, $event->getElement());
        $this->assertSame($otherNodeElement, $event->getElement());
        $relationElement = new RelationElement();
        $event->setElement($relationElement);
        $this->assertSame($relationElement, $event->getElement());
    }
}
