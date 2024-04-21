<?php

declare(strict_types=1);

namespace App\tests\UnitTests\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\EventSystem\ElementPropertyChange\EventListener\UserElementPropertyChangeEventListener;
use App\Factory\Exception\Client400ForbiddenPropertyExceptionFactory;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserElementPropertyChangeEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testElementsWhichAreNotUsersAreIgnored(): void
    {
        self::expectNotToPerformAssertions();
        $event = new ElementPropertyChangeEvent('Test', null, ['name' => 'Test']);
        $eventListener = new UserElementPropertyChangeEventListener(
            $this->prophesize(EmberNexusConfiguration::class)->reveal(),
            $this->prophesize(Client400ForbiddenPropertyExceptionFactory::class)->reveal()
        );
        $eventListener->onElementPropertyChangeEvent($event);
    }

    public function testUsersWithNoPasswordHashOrUniqueIdentifyingPropertiesAreIgnored(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getRegisterUniqueIdentifier()->willReturn('email');
        self::expectNotToPerformAssertions();
        $event = new ElementPropertyChangeEvent('User', null, ['name' => 'Test']);
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())
            ->willReturn('http://example.com/url-to-error');
        $eventListener = new UserElementPropertyChangeEventListener(
            $emberNexusConfiguration->reveal(),
            new Client400ForbiddenPropertyExceptionFactory(
                $urlGenerator->reveal()
            )
        );
        $eventListener->onElementPropertyChangeEvent($event);
    }

    public function testUserWithPasswordHashPropertyTriggersException(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getRegisterUniqueIdentifier()->willReturn('email');
        $event = new ElementPropertyChangeEvent('User', null, ['_passwordHash' => true]);
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())
            ->willReturn('http://example.com/url-to-error');
        $eventListener = new UserElementPropertyChangeEventListener(
            $emberNexusConfiguration->reveal(),
            new Client400ForbiddenPropertyExceptionFactory(
                $urlGenerator->reveal()
            )
        );
        $this->expectException(Exception::class);
        $eventListener->onElementPropertyChangeEvent($event);
    }

    public function testUserWithUniqueIdentifyingPropertyTriggersException(): void
    {
        $emberNexusConfiguration = $this->prophesize(EmberNexusConfiguration::class);
        $emberNexusConfiguration->getRegisterUniqueIdentifier()->willReturn('email');
        $event = new ElementPropertyChangeEvent('User', null, ['email' => true]);
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())
            ->willReturn('http://example.com/url-to-error');
        $eventListener = new UserElementPropertyChangeEventListener(
            $emberNexusConfiguration->reveal(),
            new Client400ForbiddenPropertyExceptionFactory(
                $urlGenerator->reveal()
            )
        );
        $this->expectException(Exception::class);
        $eventListener->onElementPropertyChangeEvent($event);
    }
}
