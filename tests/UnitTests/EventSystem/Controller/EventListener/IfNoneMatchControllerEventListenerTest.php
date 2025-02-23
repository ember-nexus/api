<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Controller\EventListener;

use App\Attribute\EndpointSupportsEtag;
use App\EventSystem\Controller\EventListener\IfNoneMatchControllerEventListener;
use App\Exception\Client412PreconditionFailedException;
use App\Factory\Exception\Client412PreconditionFailedExceptionFactory;
use App\Response\NotModifiedResponse;
use App\Service\EtagService;
use App\Type\Etag;
use App\Type\EtagType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[Small]
#[CoversClass(IfNoneMatchControllerEventListener::class)]
class IfNoneMatchControllerEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testControllerWithoutEndpointSupportsEtagAttributeAreIgnored(): void
    {
        $closure = function () {};

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $this->prophesize(Request::class)->reveal(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $eventListener = new IfNoneMatchControllerEventListener(
            $this->prophesize(EtagService::class)->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );
        $eventListener->onKernelController($event);

        $this->assertSame($closure, $event->getController());
    }

    public function testControllerWithNoEtagIsSkipped(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $this->prophesize(Request::class)->reveal(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(null);

        $eventListener = new IfNoneMatchControllerEventListener(
            $etagService->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );
        $eventListener->onKernelController($event);

        $this->assertSame($closure, $event->getController());
    }

    public function testControllerWithEtagAndNoIfNoneMatchHeaderIsSkipped(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('someEtag'));

        $eventListener = new IfNoneMatchControllerEventListener(
            $etagService->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );
        $eventListener->onKernelController($event);
        $this->assertSame($closure, $event->getController());
    }

    public function testControllerWithEtagAndNullIfNoneMatchHeaderIsSkipped(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->headers->set('If-None-Match', null);

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('someEtag'));

        $eventListener = new IfNoneMatchControllerEventListener(
            $etagService->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );
        $eventListener->onKernelController($event);
        $this->assertSame($closure, $event->getController());
    }

    public function testControllerWithEtagAndIfNoneMatchHeaderIsSkipped(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->headers->set('If-None-Match', '"someOtherEtag"');

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('someEtag'));

        $eventListener = new IfNoneMatchControllerEventListener(
            $etagService->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );

        $eventListener->onKernelController($event);
        $this->assertSame($closure, $event->getController());
    }

    public function testControllerWithEtagAndWrongIfNoneMatchHeaderReturns412ForPost(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->setMethod('POST');
        $request->headers->set('If-None-Match', '"someEtag"');

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('someEtag'));

        $client412PreconditionFailedExceptionFactory = $this->prophesize(Client412PreconditionFailedExceptionFactory::class);
        $client412PreconditionFailedExceptionFactory->createFromTemplate()->willReturn(new Client412PreconditionFailedException('title'));

        $eventListener = new IfNoneMatchControllerEventListener(
            $etagService->reveal(),
            $client412PreconditionFailedExceptionFactory->reveal()
        );

        $this->expectException(Client412PreconditionFailedException::class);

        $eventListener->onKernelController($event);
    }

    public function testControllerWithEtagAndWrongIfNoneMatchHeaderReturns412ForPut(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->setMethod('PUT');
        $request->headers->set('If-None-Match', '"someEtag"');

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('someEtag'));

        $client412PreconditionFailedExceptionFactory = $this->prophesize(Client412PreconditionFailedExceptionFactory::class);
        $client412PreconditionFailedExceptionFactory->createFromTemplate()->willReturn(new Client412PreconditionFailedException('title'));

        $eventListener = new IfNoneMatchControllerEventListener(
            $etagService->reveal(),
            $client412PreconditionFailedExceptionFactory->reveal()
        );

        $this->expectException(Client412PreconditionFailedException::class);

        $eventListener->onKernelController($event);
    }

    public function testControllerWithEtagAndWrongIfNoneMatchHeaderReturns412ForPatch(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->setMethod('PATCH');
        $request->headers->set('If-None-Match', '"someEtag"');

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('someEtag'));

        $client412PreconditionFailedExceptionFactory = $this->prophesize(Client412PreconditionFailedExceptionFactory::class);
        $client412PreconditionFailedExceptionFactory->createFromTemplate()->willReturn(new Client412PreconditionFailedException('title'));

        $eventListener = new IfNoneMatchControllerEventListener(
            $etagService->reveal(),
            $client412PreconditionFailedExceptionFactory->reveal()
        );

        $this->expectException(Client412PreconditionFailedException::class);

        $eventListener->onKernelController($event);
    }

    public function testControllerWithEtagAndWrongIfNoneMatchHeaderReturns412ForDelete(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->setMethod('DELETE');
        $request->headers->set('If-None-Match', '"someEtag"');

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('someEtag'));

        $client412PreconditionFailedExceptionFactory = $this->prophesize(Client412PreconditionFailedExceptionFactory::class);
        $client412PreconditionFailedExceptionFactory->createFromTemplate()->willReturn(new Client412PreconditionFailedException('title'));

        $eventListener = new IfNoneMatchControllerEventListener(
            $etagService->reveal(),
            $client412PreconditionFailedExceptionFactory->reveal()
        );

        $this->expectException(Client412PreconditionFailedException::class);

        $eventListener->onKernelController($event);
    }

    public function testControllerWithEtagAndWrongIfNoneMatchHeaderReturns304ForGet(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->setMethod('GET');
        $request->headers->set('If-None-Match', '"someEtag"');

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('someEtag'));

        $eventListener = new IfNoneMatchControllerEventListener(
            $etagService->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );

        $eventListener->onKernelController($event);

        $response = $event->getController()();
        $this->assertInstanceOf(NotModifiedResponse::class, $response);
    }

    public function testControllerWithEtagAndWrongIfNoneMatchHeaderReturns304ForHead(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->setMethod('HEAD');
        $request->headers->set('If-None-Match', '"someEtag"');

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('someEtag'));

        $eventListener = new IfNoneMatchControllerEventListener(
            $etagService->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );

        $eventListener->onKernelController($event);

        $response = $event->getController()();
        $this->assertInstanceOf(NotModifiedResponse::class, $response);
    }

    public function testControllerWithWeakEtagAndWrongIfNoneMatchHeaderReturns304ForHead(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->setMethod('HEAD');
        $request->headers->set('If-None-Match', 'W/someEtag"');

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('someEtag'));

        $eventListener = new IfNoneMatchControllerEventListener(
            $etagService->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );

        $eventListener->onKernelController($event);

        $response = $event->getController()();
        $this->assertInstanceOf(NotModifiedResponse::class, $response);
    }

    public function testControllerWithEtagAndMultipleIfNoneMatchHeaderIsWorking(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->headers->set('If-None-Match', '"someOtherEtag",W/"someEtag","finalEtag"');

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('finalEtag'));

        $eventListener = new IfNoneMatchControllerEventListener(
            $etagService->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );

        $eventListener->onKernelController($event);

        $response = $event->getController()();
        $this->assertInstanceOf(NotModifiedResponse::class, $response);
    }

    public function testControllerWithEtagAndMultipleIfNoneMatchHeaderAndBadWhitespaceIsWorking(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->headers->set('If-None-Match', '"someOtherEtag", W/"someEtag",    W/"finalEtag"');

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('finalEtag'));

        $eventListener = new IfNoneMatchControllerEventListener(
            $etagService->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );

        $eventListener->onKernelController($event);

        $response = $event->getController()();
        $this->assertInstanceOf(NotModifiedResponse::class, $response);
    }
}
