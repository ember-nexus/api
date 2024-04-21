<?php

declare(strict_types=1);

namespace App\tests\UnitTests\EventSystem\ElementPropertyReturn\EventListener;

use App\EventSystem\ElementPropertyReturn\Event\ElementPropertyReturnEvent;
use App\EventSystem\ElementPropertyReturn\EventListener\IdElementPropertyReturnEventListener;
use App\Type\NodeElement;
use App\Type\RelationElement;
use PHPUnit\Framework\TestCase;

class IdElementPropertyReturnEventListenerTest extends TestCase
{
    public function testEventListenerWithNode(): void
    {
        $eventListener = new IdElementPropertyReturnEventListener();
        $event = new ElementPropertyReturnEvent(new NodeElement());
        $this->assertEmpty($event->getBlockedProperties());
        $eventListener->onElementPropertyReturnEvent($event);
        $this->assertCount(1, $event->getBlockedProperties());
        $this->assertSame('id', $event->getBlockedProperties()[0]);
    }

    public function testEventListenerWithRelation(): void
    {
        $eventListener = new IdElementPropertyReturnEventListener();
        $event = new ElementPropertyReturnEvent(new RelationElement());
        $this->assertEmpty($event->getBlockedProperties());
        $eventListener->onElementPropertyReturnEvent($event);
        $this->assertCount(1, $event->getBlockedProperties());
        $this->assertSame('id', $event->getBlockedProperties()[0]);
    }
}
