<?php

declare(strict_types=1);

namespace App\tests\UnitTests\EventSystem\ElementPropertyChange\Event;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\Type\NodeElement;
use PHPUnit\Framework\TestCase;

class ElementPropertyChangeEventTest extends TestCase
{
    public function testEventReturnsCorrectWithoutElementData(): void
    {
        $event = new ElementPropertyChangeEvent('type', null, ['a' => 'A']);
        $this->assertSame('type', $event->getLabelOrType());
        $this->assertNull($event->getElement());
        $changedProperties = $event->getChangedProperties();
        $this->assertIsArray($changedProperties);
        /**
         * @var $changedProperties array<string, mixed>
         */
        $this->assertSame('A', $changedProperties['a']);
    }

    public function testEventReturnsCorrectWithElementData(): void
    {
        $element = new NodeElement();
        $element->setLabel('Element');
        $event = new ElementPropertyChangeEvent('type', $element, ['b' => 'B']);
        $this->assertSame('type', $event->getLabelOrType());
        $this->assertSame($element, $event->getElement());
        $changedProperties = $event->getChangedProperties();
        $this->assertIsArray($changedProperties);
        /**
         * @var $changedProperties array<string, mixed>
         */
        $this->assertSame('B', $changedProperties['b']);
    }
}
