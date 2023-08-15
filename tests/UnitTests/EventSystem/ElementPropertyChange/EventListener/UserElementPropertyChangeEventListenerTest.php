<?php

namespace App\Tests\UnitTests\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\EventSystem\ElementPropertyChange\EventListener\UserElementPropertyChangeEventListener;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class UserElementPropertyChangeEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testElementsWhichAreNotUsersAreIgnored(): void
    {
        self::expectNotToPerformAssertions();
        $event = new ElementPropertyChangeEvent('Test', null, ['name' => 'Test']);
        $eventListener = new UserElementPropertyChangeEventListener(
            $this->prophesize(EmberNexusConfiguration::class)->reveal()
        );
        $eventListener->onElementPropertyChangeEvent($event);
    }

    public function testUsersWithNoPasswordHashOrUniqueIdentifyingPropertiesAreIgnored(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getRegisterUniqueIdentifier()->willReturn('email');
        self::expectNotToPerformAssertions();
        $event = new ElementPropertyChangeEvent('User', null, ['name' => 'Test']);
        $eventListener = new UserElementPropertyChangeEventListener($emberNexusConfiguration->reveal());
        $eventListener->onElementPropertyChangeEvent($event);
    }

    public function testUserWithPasswordHashPropertyTriggersException(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getRegisterUniqueIdentifier()->willReturn('email');
        $event = new ElementPropertyChangeEvent('User', null, ['_passwordHash' => true]);
        $eventListener = new UserElementPropertyChangeEventListener($emberNexusConfiguration->reveal());
        $this->expectException(\Exception::class);
        $eventListener->onElementPropertyChangeEvent($event);
    }

    public function testUserWithUniqueIdentifyingPropertyTriggersException(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getRegisterUniqueIdentifier()->willReturn('email');
        $event = new ElementPropertyChangeEvent('User', null, ['email' => true]);
        $eventListener = new UserElementPropertyChangeEventListener($emberNexusConfiguration->reveal());
        $this->expectException(\Exception::class);
        $eventListener->onElementPropertyChangeEvent($event);
    }
}
