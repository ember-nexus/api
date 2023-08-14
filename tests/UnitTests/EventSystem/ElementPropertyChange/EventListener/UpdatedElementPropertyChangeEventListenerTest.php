<?php

namespace App\Tests\UnitTests\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\EventSystem\ElementPropertyChange\EventListener\UpdatedElementPropertyChangeEventListener;
use PHPUnit\Framework\TestCase;

class UpdatedElementPropertyChangeEventListenerTest extends TestCase
{
    public function testElementsWithoutUpdatedPropertyAreIgnored(): void
    {
        self::expectNotToPerformAssertions();
        $event = new ElementPropertyChangeEvent('Test', null, ['name' => 'Test']);
        $eventListener = new UpdatedElementPropertyChangeEventListener();
        $eventListener->onElementPropertyChangeEvent($event);
    }

    public function testElementWithUpdatedPropertyTriggersException(): void
    {
        $event = new ElementPropertyChangeEvent('Test', null, ['updated' => true]);
        $eventListener = new UpdatedElementPropertyChangeEventListener();
        $this->expectException(\Exception::class);
        $eventListener->onElementPropertyChangeEvent($event);
    }
}
