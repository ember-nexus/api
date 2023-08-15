<?php

namespace App\Tests\UnitTests\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\EventSystem\ElementPropertyChange\EventListener\TokenElementPropertyChangeEventListener;
use PHPUnit\Framework\TestCase;

class TokenElementPropertyChangeEventListenerTest extends TestCase
{
    public function testElementsWhichAreNotTokensAreIgnored(): void
    {
        self::expectNotToPerformAssertions();
        $event = new ElementPropertyChangeEvent('Test', null, ['name' => 'Test']);
        $eventListener = new TokenElementPropertyChangeEventListener();
        $eventListener->onElementPropertyChangeEvent($event);
    }

    public function testTokensWithNoTokenOrTokenHashPropertiesAreIgnored(): void
    {
        self::expectNotToPerformAssertions();
        $event = new ElementPropertyChangeEvent('Token', null, ['name' => 'Test']);
        $eventListener = new TokenElementPropertyChangeEventListener();
        $eventListener->onElementPropertyChangeEvent($event);
    }

    public function testTokenWithTokenPropertyTriggersException(): void
    {
        $event = new ElementPropertyChangeEvent('Token', null, ['token' => true]);
        $eventListener = new TokenElementPropertyChangeEventListener();
        $this->expectException(\Exception::class);
        $eventListener->onElementPropertyChangeEvent($event);
    }

    public function testTokenWithTokenHashPropertyTriggersException(): void
    {
        $event = new ElementPropertyChangeEvent('Token', null, ['_tokenHash' => true]);
        $eventListener = new TokenElementPropertyChangeEventListener();
        $this->expectException(\Exception::class);
        $eventListener->onElementPropertyChangeEvent($event);
    }
}