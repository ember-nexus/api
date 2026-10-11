<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Controller\EventListener;

use App\Attribute\EndpointSupportsEtag;
use App\EventSystem\Controller\EventListener\ElementRequestLockControllerEventListener;
use App\Exception\Client409ConflictException;
use App\Factory\Exception\Client409ConflictExceptionFactory;
use App\Service\ElementRequestLockService;
use App\Type\EtagType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[Small]
#[CoversClass(ElementRequestLockControllerEventListener::class)]
class ElementRequestLockControllerEventListenerTest extends TestCase
{
    use ProphecyTrait;

    private const string ELEMENT_ID = '3950e94a-2cd0-43ef-ac5a-8e116a4263c8';

    private function createListener(?ElementRequestLockService $elementRequestLockService = null, ?Client409ConflictExceptionFactory $client409ConflictExceptionFactory = null): ElementRequestLockControllerEventListener
    {
        return new ElementRequestLockControllerEventListener(
            $elementRequestLockService ?? $this->prophesize(ElementRequestLockService::class)->reveal(),
            $client409ConflictExceptionFactory ?? $this->prophesize(Client409ConflictExceptionFactory::class)->reveal(),
        );
    }

    private function createControllerEvent(callable $controller, Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): ControllerEvent
    {
        return new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $controller,
            $request,
            $requestType
        );
    }

    public function testSubRequestsAreIgnored(): void
    {
        self::expectNotToPerformAssertions();

        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;
        $request = Request::create('/'.self::ELEMENT_ID, 'PATCH');
        $request->attributes->set('id', self::ELEMENT_ID);

        $event = $this->createControllerEvent($closure, $request, HttpKernelInterface::SUB_REQUEST);

        $this->createListener()->onKernelController($event);
    }

    public function testNonLockedMethodIsIgnored(): void
    {
        self::expectNotToPerformAssertions();

        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;
        $request = Request::create('/'.self::ELEMENT_ID, 'GET');
        $request->attributes->set('id', self::ELEMENT_ID);

        $event = $this->createControllerEvent($closure, $request);

        $this->createListener()->onKernelController($event);
    }

    public function testControllerWithoutEndpointSupportsEtagAttributeIsIgnored(): void
    {
        self::expectNotToPerformAssertions();

        $closure = fn () => true;
        $request = Request::create('/'.self::ELEMENT_ID, 'PATCH');
        $request->attributes->set('id', self::ELEMENT_ID);

        $event = $this->createControllerEvent($closure, $request);

        $this->createListener()->onKernelController($event);
    }

    public function testNonElementEtagTypeIsIgnored(): void
    {
        self::expectNotToPerformAssertions();

        $closure = #[EndpointSupportsEtag(EtagType::FILE)]
        fn () => true;
        $request = Request::create('/'.self::ELEMENT_ID, 'PUT');
        $request->attributes->set('id', self::ELEMENT_ID);

        $event = $this->createControllerEvent($closure, $request);

        $this->createListener()->onKernelController($event);
    }

    public function testMissingIdAttributeIsIgnored(): void
    {
        self::expectNotToPerformAssertions();

        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;
        $request = Request::create('/', 'DELETE');

        $event = $this->createControllerEvent($closure, $request);

        $this->createListener()->onKernelController($event);
    }

    public function testLockIsAcquiredAndStoredOnTheRequest(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;
        $request = Request::create('/'.self::ELEMENT_ID, 'PATCH');
        $request->attributes->set('id', self::ELEMENT_ID);

        $event = $this->createControllerEvent($closure, $request);

        $elementRequestLockService = $this->prophesize(ElementRequestLockService::class);
        $elementRequestLockService->acquire(Uuid::fromString(self::ELEMENT_ID))->shouldBeCalledOnce()->willReturn('request-token');

        $this->createListener($elementRequestLockService->reveal())->onKernelController($event);

        $this->assertEquals(UuidV4::fromString(self::ELEMENT_ID), $request->attributes->get('_elementRequestLockElementId'));
        $this->assertSame('request-token', $request->attributes->get('_elementRequestLockToken'));
    }

    public function testLockContentionThrowsConflict(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;
        $request = Request::create('/'.self::ELEMENT_ID, 'PATCH');
        $request->attributes->set('id', self::ELEMENT_ID);

        $event = $this->createControllerEvent($closure, $request);

        $elementRequestLockService = $this->prophesize(ElementRequestLockService::class);
        $elementRequestLockService->acquire(Uuid::fromString(self::ELEMENT_ID))->willReturn(null);

        $client409ConflictExceptionFactory = $this->prophesize(Client409ConflictExceptionFactory::class);
        $client409ConflictExceptionFactory->createFromDetail(Argument::any())->willReturn(new Client409ConflictException('title'));

        $this->expectException(Client409ConflictException::class);

        $this->createListener($elementRequestLockService->reveal(), $client409ConflictExceptionFactory->reveal())->onKernelController($event);
    }

    private function createResponseEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        return new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            $requestType,
            new Response()
        );
    }

    public function testResponseReleasesTheLockAndClearsTheRequestAttributes(): void
    {
        $request = Request::create('/'.self::ELEMENT_ID, 'PATCH');
        $elementId = UuidV4::fromString(self::ELEMENT_ID);
        $request->attributes->set('_elementRequestLockElementId', $elementId);
        $request->attributes->set('_elementRequestLockToken', 'request-token');

        $elementRequestLockService = $this->prophesize(ElementRequestLockService::class);
        $elementRequestLockService->release($elementId, 'request-token')->shouldBeCalledOnce();

        $this->createListener($elementRequestLockService->reveal())->onKernelResponse($this->createResponseEvent($request));

        $this->assertFalse($request->attributes->has('_elementRequestLockElementId'));
        $this->assertFalse($request->attributes->has('_elementRequestLockToken'));
    }

    public function testResponseWithoutLockAttributesDoesNotRelease(): void
    {
        $request = Request::create('/'.self::ELEMENT_ID, 'GET');

        $elementRequestLockService = $this->prophesize(ElementRequestLockService::class);
        $elementRequestLockService->release(Argument::cetera())->shouldNotBeCalled();

        $this->createListener($elementRequestLockService->reveal())->onKernelResponse($this->createResponseEvent($request));
    }

    public function testSubRequestResponseIsIgnored(): void
    {
        $request = Request::create('/'.self::ELEMENT_ID, 'PATCH');
        $elementId = UuidV4::fromString(self::ELEMENT_ID);
        $request->attributes->set('_elementRequestLockElementId', $elementId);
        $request->attributes->set('_elementRequestLockToken', 'request-token');

        $elementRequestLockService = $this->prophesize(ElementRequestLockService::class);
        $elementRequestLockService->release(Argument::cetera())->shouldNotBeCalled();

        $this->createListener($elementRequestLockService->reveal())->onKernelResponse($this->createResponseEvent($request, HttpKernelInterface::SUB_REQUEST));
    }
}
