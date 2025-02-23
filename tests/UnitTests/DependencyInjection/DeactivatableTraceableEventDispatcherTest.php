<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\DependencyInjection;

use App\DependencyInjection\DeactivatableTraceableEventDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

#[Small]
#[CoversClass(DeactivatableTraceableEventDispatcher::class)]
class DeactivatableTraceableEventDispatcherTest extends TestCase
{
    use ProphecyTrait;

    public function testIsDeactivated(): void
    {
        $dispatcher = $this->prophesize(EventDispatcherInterface::class)->reveal();
        $stopwatch = $this->prophesize(Stopwatch::class)->reveal();
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $deactivatableTraceableEventDispatcher = new DeactivatableTraceableEventDispatcher(
            $dispatcher,
            $stopwatch,
            $logger
        );

        $this->assertFalse($deactivatableTraceableEventDispatcher->isDeactivated());
    }

    public function testDeactivate(): void
    {
        $dispatcher = $this->prophesize(EventDispatcherInterface::class)->reveal();
        $stopwatch = $this->prophesize(Stopwatch::class)->reveal();
        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->notice(Argument::is('Deactivated TraceableEventDispatcher'))
            ->shouldBeCalledOnce();

        $deactivatableTraceableEventDispatcher = new DeactivatableTraceableEventDispatcher(
            $dispatcher,
            $stopwatch,
            $logger->reveal()
        );

        $this->assertSame($deactivatableTraceableEventDispatcher, $deactivatableTraceableEventDispatcher->deactivate());
        $this->assertTrue($deactivatableTraceableEventDispatcher->isDeactivated());
    }

    public function testDeactivateWithoutLogger(): void
    {
        $dispatcher = $this->prophesize(EventDispatcherInterface::class)->reveal();
        $stopwatch = $this->prophesize(Stopwatch::class)->reveal();

        $deactivatableTraceableEventDispatcher = new DeactivatableTraceableEventDispatcher(
            $dispatcher,
            $stopwatch,
        );

        $this->assertSame($deactivatableTraceableEventDispatcher, $deactivatableTraceableEventDispatcher->deactivate());
        $this->assertTrue($deactivatableTraceableEventDispatcher->isDeactivated());
    }

    public function testActivate(): void
    {
        $dispatcher = $this->prophesize(EventDispatcherInterface::class)->reveal();
        $stopwatch = $this->prophesize(Stopwatch::class)->reveal();
        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->notice(Argument::is('Activated TraceableEventDispatcher'))
            ->shouldBeCalledOnce();

        $deactivatableTraceableEventDispatcher = new DeactivatableTraceableEventDispatcher(
            $dispatcher,
            $stopwatch,
            $logger->reveal()
        );

        $this->assertSame($deactivatableTraceableEventDispatcher, $deactivatableTraceableEventDispatcher->activate());
        $this->assertFalse($deactivatableTraceableEventDispatcher->isDeactivated());
    }

    public function testActivateWithoutLogger(): void
    {
        $dispatcher = $this->prophesize(EventDispatcherInterface::class)->reveal();
        $stopwatch = $this->prophesize(Stopwatch::class)->reveal();

        $deactivatableTraceableEventDispatcher = new DeactivatableTraceableEventDispatcher(
            $dispatcher,
            $stopwatch
        );

        $this->assertSame($deactivatableTraceableEventDispatcher, $deactivatableTraceableEventDispatcher->activate());
        $this->assertFalse($deactivatableTraceableEventDispatcher->isDeactivated());
    }

    public function testDispatchWhenActive(): void
    {
        $event = new stdClass();
        $eventName = 'testEvent';

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher
            ->dispatch(Argument::is($event), Argument::is($eventName))
            ->shouldBeCalledOnce()
            ->willReturn($event);
        $dispatcher
            ->hasListeners(Argument::is($eventName))
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $dispatcher
            ->getListeners(Argument::is($eventName))
            ->shouldBeCalledOnce()
            ->willReturn([]);
        $stopwatch = new Stopwatch();
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $deactivatableTraceableEventDispatcher = new DeactivatableTraceableEventDispatcher(
            $dispatcher->reveal(),
            $stopwatch,
            $logger
        );

        $this->assertSame($deactivatableTraceableEventDispatcher, $deactivatableTraceableEventDispatcher->activate());
        $this->assertSame($event, $deactivatableTraceableEventDispatcher->dispatch($event, $eventName));
    }

    public function testDispatchWhenDeactivated(): void
    {
        $event = new stdClass();
        $eventName = 'testEvent';

        $dispatcher = $this->prophesize(EventDispatcherInterface::class);
        $dispatcher
            ->dispatch(Argument::is($event), Argument::is($eventName))
            ->shouldBeCalledOnce()
            ->willReturn($event);
        $stopwatch = $this->prophesize(Stopwatch::class)->reveal();
        $logger = $this->prophesize(LoggerInterface::class)->reveal();

        $deactivatableTraceableEventDispatcher = new DeactivatableTraceableEventDispatcher(
            $dispatcher->reveal(),
            $stopwatch,
            $logger
        );

        $this->assertSame($deactivatableTraceableEventDispatcher, $deactivatableTraceableEventDispatcher->deactivate());
        $this->assertSame($event, $deactivatableTraceableEventDispatcher->dispatch($event, $eventName));
    }
}
