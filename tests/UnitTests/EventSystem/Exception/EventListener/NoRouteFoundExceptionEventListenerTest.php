<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Exception\EventListener;

use App\EventSystem\Exception\EventListener\NoRouteFoundExceptionEventListener;
use App\Exception\Client404NotFoundException;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[Small]
#[CoversClass(NoRouteFoundExceptionEventListener::class)]
class NoRouteFoundExceptionEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testWithDifferentException(): void
    {
        $client404NotFoundExceptionFactory = $this->prophesize(Client404NotFoundExceptionFactory::class)->reveal();

        $throwable = null;
        try {
            throw new Exception('Some different exception');
        } catch (Exception $exception) {
            $throwable = $exception;
        }
        $event = new ExceptionEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $this->prophesize(Request::class)->reveal(),
            0,
            $throwable
        );

        $this->expectNotToPerformAssertions();

        $noRouteFoundExceptionEventListener = new NoRouteFoundExceptionEventListener(
            $client404NotFoundExceptionFactory
        );

        $noRouteFoundExceptionEventListener->onKernelException($event);
    }

    public function testWithNotFoundHttpException(): void
    {
        $throwable = null;
        try {
            throw new NotFoundHttpException('Some bad exception');
        } catch (NotFoundHttpException $exception) {
            $throwable = $exception;
        }

        $client404NotFoundException = new Client404NotFoundException('some type');

        $client404NotFoundExceptionFactory = $this->prophesize(Client404NotFoundExceptionFactory::class);
        $client404NotFoundExceptionFactory
            ->createFromTemplate(Argument::is($throwable))
            ->shouldBeCalledOnce()
            ->willReturn($client404NotFoundException);

        $event = new ExceptionEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $this->prophesize(Request::class)->reveal(),
            0,
            $throwable
        );

        $noRouteFoundExceptionEventListener = new NoRouteFoundExceptionEventListener(
            $client404NotFoundExceptionFactory->reveal()
        );

        $this->expectExceptionObject($client404NotFoundException);

        $noRouteFoundExceptionEventListener->onKernelException($event);
    }
}
