<?php

namespace App\Tests\UnitTests\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\EventSystem\ElementPropertyChange\EventListener\IdElementPropertyChangeEventListener;
use PHPUnit\Framework\TestCase;

class IdElementPropertyChangeEventListenerTest extends TestCase
{
    public function testElementsWithoutIdPropertyAreIgnored(): void
    {
        self::expectNotToPerformAssertions();
        $event = new ElementPropertyChangeEvent('Test', null, ['name' => 'Test']);
        $eventListener = new IdElementPropertyChangeEventListener();
        $eventListener->onElementPropertyChangeEvent($event);
    }

    public function testElementWithIdPropertyTriggersException(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }
        $event = new ElementPropertyChangeEvent('Test', null, ['id' => true]);
        $eventListener = new IdElementPropertyChangeEventListener();
        $this->expectException(\Exception::class);
        $eventListener->onElementPropertyChangeEvent($event);
    }
}
