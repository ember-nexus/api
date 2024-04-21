<?php

declare(strict_types=1);

namespace App\tests\UnitTests\EventSystem\Controller\EventListener;

use App\Attribute\EndpointSupportsEtag;
use App\EventSystem\Controller\EventListener\IfMatchControllerEventListener;
use App\Exception\Client412PreconditionFailedException;
use App\Factory\Exception\Client412PreconditionFailedExceptionFactory;
use App\Service\EtagService;
use App\Type\Etag;
use App\Type\EtagType;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class IfMatchControllerEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testControllerWithoutEndpointSupportsEtagAttributeAreIgnored(): void
    {
        self::expectNotToPerformAssertions();

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            function () {},
            $this->prophesize(Request::class)->reveal(),
            HttpKernelInterface::MAIN_REQUEST
        );

        $eventListener = new IfMatchControllerEventListener(
            $this->prophesize(EtagService::class)->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );
        $eventListener->onKernelController($event);
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

        $eventListener = new IfMatchControllerEventListener(
            $etagService->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );
        $eventListener->onKernelController($event);
    }

    public function testControllerWithEtagAndNoIfMatchHeaderIsSkipped(): void
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

        $eventListener = new IfMatchControllerEventListener(
            $etagService->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );
        $eventListener->onKernelController($event);
    }

    public function testControllerWithEtagAndNullIfMatchHeaderIsSkipped(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->headers->set('If-Match', null);

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('someEtag'));

        $eventListener = new IfMatchControllerEventListener(
            $etagService->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );
        $eventListener->onKernelController($event);
    }

    public function testControllerWithEtagAndWeakIfMatchHeaderIsSkipped(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->headers->set('If-Match', 'W/"someEtag"');

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

        $eventListener = new IfMatchControllerEventListener(
            $etagService->reveal(),
            $client412PreconditionFailedExceptionFactory->reveal()
        );

        $this->expectException(Client412PreconditionFailedException::class);

        $eventListener->onKernelController($event);
    }

    public function testControllerWithEtagAndWrongIfMatchHeaderIsSkipped(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->headers->set('If-Match', '"someOtherEtag"');

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

        $eventListener = new IfMatchControllerEventListener(
            $etagService->reveal(),
            $client412PreconditionFailedExceptionFactory->reveal()
        );

        $this->expectException(Client412PreconditionFailedException::class);

        $eventListener->onKernelController($event);
    }

    public function testControllerWithEtagAndIfMatchHeaderIsWorking(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->headers->set('If-Match', '"someEtag"');

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('someEtag'));

        $eventListener = new IfMatchControllerEventListener(
            $etagService->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );

        $eventListener->onKernelController($event);
    }

    public function testControllerWithEtagAndMultipleIfMatchHeaderIsWorking(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = new Request();
        $request->headers->set('If-Match', '"someOtherEtag", W/"someEtag", "someEtag"');

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('someEtag'));

        $eventListener = new IfMatchControllerEventListener(
            $etagService->reveal(),
            $this->prophesize(Client412PreconditionFailedExceptionFactory::class)->reveal()
        );

        $eventListener->onKernelController($event);
    }
}
