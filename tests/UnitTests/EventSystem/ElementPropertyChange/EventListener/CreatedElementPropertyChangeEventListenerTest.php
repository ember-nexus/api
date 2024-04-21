<?php

declare(strict_types=1);

namespace App\tests\UnitTests\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\EventSystem\ElementPropertyChange\EventListener\CreatedElementPropertyChangeEventListener;
use App\Factory\Exception\Client400ForbiddenPropertyExceptionFactory;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CreatedElementPropertyChangeEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testElementsWithoutCreatedPropertyAreIgnored(): void
    {
        self::expectNotToPerformAssertions();
        $event = new ElementPropertyChangeEvent('Test', null, ['name' => 'Test']);
        $eventListener = new CreatedElementPropertyChangeEventListener(
            $this->prophesize(Client400ForbiddenPropertyExceptionFactory::class)->reveal()
        );
        $eventListener->onElementPropertyChangeEvent($event);
    }

    public function testElementWithCreatedPropertyTriggersException(): void
    {
        $event = new ElementPropertyChangeEvent('Test', null, ['created' => true]);
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())
            ->willReturn('http://example.com/url-to-error');
        $eventListener = new CreatedElementPropertyChangeEventListener(
            new Client400ForbiddenPropertyExceptionFactory(
                $urlGenerator->reveal()
            )
        );
        $this->expectException(Exception::class);
        $eventListener->onElementPropertyChangeEvent($event);
    }
}
