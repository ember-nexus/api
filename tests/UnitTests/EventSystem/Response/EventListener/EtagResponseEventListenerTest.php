<?php

namespace App\tests\UnitTests\EventSystem\Response\EventListener;

use App\EventSystem\Response\EventListener\EtagResponseEventListener;
use App\Response\CollectionResponse;
use App\Response\ElementResponse;
use App\Service\EtagService;
use App\Type\Etag;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class EtagResponseEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testResponseOfUnknownTypeIsIgnored(): void
    {
        self::expectNotToPerformAssertions();

        $response = new Response('some content');

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $this->prophesize(Request::class)->reveal(),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $eventListener = new EtagResponseEventListener(
            $this->prophesize(EtagService::class)->reveal()
        );
        $eventListener->onKernelResponse($event);
    }

    public function testCollectionResponseIsNotIgnored(): void
    {
        $response = new CollectionResponse();

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $this->prophesize(Request::class)->reveal(),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(null);

        $eventListener = new EtagResponseEventListener(
            $etagService->reveal()
        );
        $eventListener->onKernelResponse($event);
    }

    public function testElementResponseIsNotIgnored(): void
    {
        $response = new ElementResponse();

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $this->prophesize(Request::class)->reveal(),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(null);

        $eventListener = new EtagResponseEventListener(
            $etagService->reveal()
        );
        $eventListener->onKernelResponse($event);
    }

    public function testElementResponseAndEtagWillSetEtagOnResponse(): void
    {
        $response = new ElementResponse();

        $event = new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $this->prophesize(Request::class)->reveal(),
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->getCurrentRequestEtag()->shouldBeCalledOnce()->willReturn(new Etag('someEtag'));

        $eventListener = new EtagResponseEventListener(
            $etagService->reveal()
        );
        $eventListener->onKernelResponse($event);
        $this->assertTrue($response->headers->has('Etag'));
        $this->assertSame('"someEtag"', $response->headers->get('Etag'));
    }
}
