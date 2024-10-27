<?php

declare(strict_types=1);

namespace App\tests\UnitTests\EventSystem\Exception\EventListener;

use App\EventSystem\Exception\EventListener\JsonExceptionEventListener;
use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use Exception;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Safe\Exceptions\JsonException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Throwable;

class JsonExceptionEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testJsonExceptionEventListenerIgnoresDifferentExceptions(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);

        $jsonExceptionEventListener = new JsonExceptionEventListener($client400BadContentExceptionFactory);

        $exception = new Exception('Demo exception.');

        $exceptionEvent = new ExceptionEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $this->prophesize(Request::class)->reveal(),
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        $this->expectNotToPerformAssertions();

        $jsonExceptionEventListener->onKernelException($exceptionEvent);
    }

    public function testJsonExceptionEventListenerActsOnJsonException(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('url');
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);

        $jsonExceptionEventListener = new JsonExceptionEventListener($client400BadContentExceptionFactory);

        $exception = new JsonException('Demo message');

        $exceptionEvent = new ExceptionEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $this->prophesize(Request::class)->reveal(),
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );

        try {
            $jsonExceptionEventListener->onKernelException($exceptionEvent);
        } catch (Throwable $throwable) {
            $this->assertInstanceOf(Client400BadContentException::class, $throwable);
            $this->assertSame('Unable to parse request as JSON. Demo message.', $throwable->getDetail());
            $this->assertSame($exception, $throwable->getPrevious());
        }
    }
}
