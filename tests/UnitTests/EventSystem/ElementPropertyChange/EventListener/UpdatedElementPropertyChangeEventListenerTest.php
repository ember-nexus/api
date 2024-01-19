<?php

namespace App\tests\UnitTests\EventSystem\ElementPropertyChange\EventListener;

use App\EventSystem\ElementPropertyChange\Event\ElementPropertyChangeEvent;
use App\EventSystem\ElementPropertyChange\EventListener\UpdatedElementPropertyChangeEventListener;
use App\Factory\Exception\Client400ForbiddenPropertyExceptionFactory;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UpdatedElementPropertyChangeEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testElementsWithoutUpdatedPropertyAreIgnored(): void
    {
        self::expectNotToPerformAssertions();
        $event = new ElementPropertyChangeEvent('Test', null, ['name' => 'Test']);
        $eventListener = new UpdatedElementPropertyChangeEventListener(
            $this->prophesize(Client400ForbiddenPropertyExceptionFactory::class)->reveal()
        );
        $eventListener->onElementPropertyChangeEvent($event);
    }

    public function testElementWithUpdatedPropertyTriggersException(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }
        $event = new ElementPropertyChangeEvent('Test', null, ['updated' => true]);
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())
            ->willReturn('http://example.com/url-to-error');
        $eventListener = new UpdatedElementPropertyChangeEventListener(
            new Client400ForbiddenPropertyExceptionFactory(
                $urlGenerator->reveal()
            )
        );
        $this->expectException(Exception::class);
        $eventListener->onElementPropertyChangeEvent($event);
    }
}
