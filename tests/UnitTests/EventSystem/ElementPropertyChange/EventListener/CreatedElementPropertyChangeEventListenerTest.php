<?php

namespace App\Tests\UnitTests\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\EventSystem\ElementPropertyChange\EventListener\CreatedElementPropertyChangeEventListener;
use Exception;
use PHPUnit\Framework\TestCase;

class CreatedElementPropertyChangeEventListenerTest extends TestCase
{
    public function testElementsWithoutCreatedPropertyAreIgnored(): void
    {
        self::expectNotToPerformAssertions();
        $event = new ElementPropertyChangeEvent('Test', null, ['name' => 'Test']);
        $eventListener = new CreatedElementPropertyChangeEventListener();
        $eventListener->onElementPropertyChangeEvent($event);
    }

    public function testElementWithCreatedPropertyTriggersException(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }
        $event = new ElementPropertyChangeEvent('Test', null, ['created' => true]);
        $eventListener = new CreatedElementPropertyChangeEventListener();
        $this->expectException(Exception::class);
        $eventListener->onElementPropertyChangeEvent($event);
    }
}
