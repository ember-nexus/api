<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementPropertyReturn\Event;

use App\EventSystem\ElementPropertyReturn\Event\ElementPropertyReturnEvent;
use App\Type\NodeElement;
use App\Type\RelationElement;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ElementPropertyReturnEvent::class)]
class ElementPropertyReturnEventTest extends TestCase
{
    public function testEventWithEmptyNode(): void
    {
        $node = new NodeElement();
        $event = new ElementPropertyReturnEvent($node);
        $this->assertSame($node, $event->getElement());
        $this->assertEmpty($event->getBlockedProperties());
        $this->assertEmpty($event->getElementPropertiesWhichAreNotOnBlacklist());
        $event->addBlockedProperty('blocked');
        $this->assertCount(1, $event->getBlockedProperties());
        $this->assertSame('blocked', $event->getBlockedProperties()[0]);
        $this->assertEmpty($event->getElementPropertiesWhichAreNotOnBlacklist());
    }

    public function testEventWithNode(): void
    {
        $node = new NodeElement();
        $node->addProperties([
            'allowed' => 1234,
            'blocked' => 4321,
        ]);
        $event = new ElementPropertyReturnEvent($node);
        $this->assertSame($node, $event->getElement());
        $this->assertEmpty($event->getBlockedProperties());
        $this->assertCount(2, $event->getElementPropertiesWhichAreNotOnBlacklist());
        $event->addBlockedProperty('blocked');
        $this->assertCount(1, $event->getBlockedProperties());
        $this->assertSame('blocked', $event->getBlockedProperties()[0]);
        $this->assertCount(1, $event->getElementPropertiesWhichAreNotOnBlacklist());
        $this->assertSame('allowed', array_keys($event->getElementPropertiesWhichAreNotOnBlacklist())[0]);
    }

    public function testEventWithEmptyRelation(): void
    {
        $relation = new RelationElement();
        $event = new ElementPropertyReturnEvent($relation);
        $this->assertSame($relation, $event->getElement());
        $this->assertEmpty($event->getBlockedProperties());
        $this->assertEmpty($event->getElementPropertiesWhichAreNotOnBlacklist());
        $event->addBlockedProperty('blocked');
        $this->assertCount(1, $event->getBlockedProperties());
        $this->assertSame('blocked', $event->getBlockedProperties()[0]);
        $this->assertEmpty($event->getElementPropertiesWhichAreNotOnBlacklist());
    }

    public function testEventWithRelation(): void
    {
        $relation = new RelationElement();
        $relation->addProperties([
            'allowed' => 1234,
            'blocked' => 4321,
        ]);
        $event = new ElementPropertyReturnEvent($relation);
        $this->assertSame($relation, $event->getElement());
        $this->assertEmpty($event->getBlockedProperties());
        $this->assertCount(2, $event->getElementPropertiesWhichAreNotOnBlacklist());
        $event->addBlockedProperty('blocked');
        $this->assertCount(1, $event->getBlockedProperties());
        $this->assertSame('blocked', $event->getBlockedProperties()[0]);
        $this->assertCount(1, $event->getElementPropertiesWhichAreNotOnBlacklist());
        $this->assertSame('allowed', array_keys($event->getElementPropertiesWhichAreNotOnBlacklist())[0]);
    }

    public function testPropagation(): void
    {
        $event = new ElementPropertyReturnEvent(new NodeElement());
        $this->assertFalse($event->isPropagationStopped());
        $event->stopPropagation();
        $this->assertTrue($event->isPropagationStopped());
    }
}
