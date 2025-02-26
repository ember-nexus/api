<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Exception\EventListener;

use App\EventSystem\Exception\EventListener\ExceptionEventListener;
use App\Exception\Client403ForbiddenException;
use App\Exception\Server500InternalServerErrorException;
use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use App\Response\ProblemJsonResponse;
use App\Tests\UnitTests\AssertLoggerTrait;
use Beste\Psr\Log\TestLogger;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(ExceptionEventListener::class)]
class ExceptionEventListenerTest extends TestCase
{
    use ProphecyTrait;
    use AssertLoggerTrait;

    public function testBuildResponseForNormalExceptionInProduction(): void
    {
        $throwable = null;
        try {
            throw new Exception('Some normal exception');
        } catch (Exception $exception) {
            $throwable = $exception;
        }

        $server500InternalServerErrorException = new Server500InternalServerErrorException('someType');

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(Argument::is('problem-unknown'))
            ->shouldBeCalledOnce()
            ->willThrow(new RouteNotFoundException());
        $kernel = $this->prophesize(KernelInterface::class);
        $kernel
            ->isDebug()
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $logger = TestLogger::create();
        $server500InternalServerErrorExceptionFactory = $this->prophesize(Server500InternalServerErrorExceptionFactory::class);
        $server500InternalServerErrorExceptionFactory
            ->createFromTemplate(
                Argument::is('Other internal exception.'),
                Argument::is([
                    'originalException' => $throwable,
                ])
            )
            ->shouldBeCalledOnce()
            ->willReturn($server500InternalServerErrorException);

        $exceptionEventListener = new ExceptionEventListener(
            $urlGenerator->reveal(),
            $kernel->reveal(),
            $logger,
            $server500InternalServerErrorExceptionFactory->reveal()
        );

        $exceptionEvent = new ExceptionEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $this->prophesize(Request::class)->reveal(),
            0,
            $throwable
        );
        $this->assertFalse($exceptionEvent->isPropagationStopped());

        $exceptionEventListener->onKernelException($exceptionEvent);

        $this->assertLogHappened($logger, 'error', 'someType Internal server error:');

        $response = $exceptionEvent->getResponse();
        $this->assertInstanceOf(ProblemJsonResponse::class, $response);
        /**
         * @var ProblemJsonResponse $response
         */
        $this->assertSame('{"type":"someType","title":"Internal server error","status":500}', $response->getContent());
        $this->assertSame(500, $response->getStatusCode());
        $this->assertTrue($exceptionEvent->isPropagationStopped());
    }

    public function testBuildResponseForNormalExceptionInDevelopment(): void
    {
        $throwable = null;
        try {
            throw new Exception('Some normal exception');
        } catch (Exception $exception) {
            $throwable = $exception;
        }

        $server500InternalServerErrorException = new Server500InternalServerErrorException('someType');

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(Argument::is('problem-unknown'))
            ->shouldBeCalledOnce()
            ->willThrow(new RouteNotFoundException());
        $kernel = $this->prophesize(KernelInterface::class);
        $kernel
            ->isDebug()
            ->shouldBeCalledOnce()
            ->willReturn(true);
        $logger = TestLogger::create();
        $server500InternalServerErrorExceptionFactory = $this->prophesize(Server500InternalServerErrorExceptionFactory::class);
        $server500InternalServerErrorExceptionFactory
            ->createFromTemplate(
                Argument::is('Other internal exception.'),
                Argument::is([
                    'originalException' => $throwable,
                ])
            )
            ->shouldBeCalledOnce()
            ->willReturn($server500InternalServerErrorException);

        $exceptionEventListener = new ExceptionEventListener(
            $urlGenerator->reveal(),
            $kernel->reveal(),
            $logger,
            $server500InternalServerErrorExceptionFactory->reveal()
        );

        $exceptionEvent = new ExceptionEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $this->prophesize(Request::class)->reveal(),
            0,
            $throwable
        );
        $this->assertFalse($exceptionEvent->isPropagationStopped());

        $exceptionEventListener->onKernelException($exceptionEvent);

        $this->assertLogHappened($logger, 'error', 'someType Internal server error:');

        $response = $exceptionEvent->getResponse();
        $this->assertInstanceOf(ProblemJsonResponse::class, $response);
        $responseData = json_decode($response->getContent(), true);
        $this->assertSame('someType', $responseData['type']);
        $this->assertSame('Internal server error', $responseData['title']);
        $this->assertSame(500, $responseData['status']);
        $this->assertArrayHasKey('exception', $responseData);
        $this->assertIsArray($responseData['exception']);
        $this->assertSame('Some normal exception', $responseData['exception']['message']);
        $this->assertArrayHasKey('trace', $responseData['exception']);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertTrue($exceptionEvent->isPropagationStopped());
    }

    public function testBuildResponseForExtendedExceptionInProduction(): void
    {
        $throwable = null;
        try {
            throw new Client403ForbiddenException('Some extended exception');
        } catch (Client403ForbiddenException $exception) {
            $throwable = $exception;
        }

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(Argument::is('problem-unknown'))
            ->shouldBeCalledOnce()
            ->willThrow(new RouteNotFoundException());
        $kernel = $this->prophesize(KernelInterface::class);
        $kernel
            ->isDebug()
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $logger = TestLogger::create();
        $server500InternalServerErrorExceptionFactory = $this->prophesize(Server500InternalServerErrorExceptionFactory::class);

        $exceptionEventListener = new ExceptionEventListener(
            $urlGenerator->reveal(),
            $kernel->reveal(),
            $logger,
            $server500InternalServerErrorExceptionFactory->reveal()
        );

        $exceptionEvent = new ExceptionEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $this->prophesize(Request::class)->reveal(),
            0,
            $throwable
        );
        $this->assertFalse($exceptionEvent->isPropagationStopped());

        $exceptionEventListener->onKernelException($exceptionEvent);

        $this->assertLogHappened($logger, 'error', 'Some extended exception Forbidden:');

        $response = $exceptionEvent->getResponse();
        $this->assertInstanceOf(ProblemJsonResponse::class, $response);
        /**
         * @var ProblemJsonResponse $response
         */
        $this->assertSame('{"type":"Some extended exception","title":"Forbidden","status":403,"detail":"Requested endpoint, element or action is forbidden."}', $response->getContent());
        $this->assertSame(403, $response->getStatusCode());
        $this->assertTrue($exceptionEvent->isPropagationStopped());
    }

    public function testBuildResponseForExtendedExceptionInDevelopment(): void
    {
        $throwable = null;
        try {
            throw new Client403ForbiddenException('Some extended exception');
        } catch (Client403ForbiddenException $exception) {
            $throwable = $exception;
        }

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(Argument::is('problem-unknown'))
            ->shouldBeCalledOnce()
            ->willThrow(new RouteNotFoundException());
        $kernel = $this->prophesize(KernelInterface::class);
        $kernel
            ->isDebug()
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $logger = TestLogger::create();
        $server500InternalServerErrorExceptionFactory = $this->prophesize(Server500InternalServerErrorExceptionFactory::class);

        $exceptionEventListener = new ExceptionEventListener(
            $urlGenerator->reveal(),
            $kernel->reveal(),
            $logger,
            $server500InternalServerErrorExceptionFactory->reveal()
        );

        $exceptionEvent = new ExceptionEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $this->prophesize(Request::class)->reveal(),
            0,
            $throwable
        );
        $this->assertFalse($exceptionEvent->isPropagationStopped());

        $exceptionEventListener->onKernelException($exceptionEvent);

        $this->assertLogHappened($logger, 'error', 'Some extended exception Forbidden:');

        $response = $exceptionEvent->getResponse();
        $this->assertInstanceOf(ProblemJsonResponse::class, $response);
        $responseData = json_decode($response->getContent(), true);
        $this->assertSame('Some extended exception', $responseData['type']);
        $this->assertSame('Forbidden', $responseData['title']);
        $this->assertSame(403, $responseData['status']);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertTrue($exceptionEvent->isPropagationStopped());
    }

    public function testInstanceLinkCanBeGenerated(): void
    {
        $throwable = null;
        try {
            throw new Client403ForbiddenException('Some extended exception', instance: 'instance123');
        } catch (Client403ForbiddenException $exception) {
            $throwable = $exception;
        }

        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(Argument::is('problem-instance123'))
            ->shouldBeCalledOnce()
            ->willReturn('https://some-link.example');
        $kernel = $this->prophesize(KernelInterface::class);
        $kernel
            ->isDebug()
            ->shouldBeCalledOnce()
            ->willReturn(false);
        $logger = TestLogger::create();
        $server500InternalServerErrorExceptionFactory = $this->prophesize(Server500InternalServerErrorExceptionFactory::class);

        $exceptionEventListener = new ExceptionEventListener(
            $urlGenerator->reveal(),
            $kernel->reveal(),
            $logger,
            $server500InternalServerErrorExceptionFactory->reveal()
        );

        $exceptionEvent = new ExceptionEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $this->prophesize(Request::class)->reveal(),
            0,
            $throwable
        );
        $this->assertFalse($exceptionEvent->isPropagationStopped());

        $exceptionEventListener->onKernelException($exceptionEvent);

        $this->assertLogHappened($logger, 'error', 'Some extended exception Forbidden:');

        $response = $exceptionEvent->getResponse();
        $this->assertInstanceOf(ProblemJsonResponse::class, $response);
        /**
         * @var ProblemJsonResponse $response
         */
        $this->assertSame('{"type":"Some extended exception","title":"Forbidden","status":403,"instance":"https://some-link.example","detail":"Requested endpoint, element or action is forbidden."}', $response->getContent());
        $this->assertSame(403, $response->getStatusCode());
        $this->assertTrue($exceptionEvent->isPropagationStopped());
    }
}
