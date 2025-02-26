<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Controller\EventListener;

use App\Attribute\EndpointSupportsEtag;
use App\EventSystem\Controller\EventListener\EtagControllerEventListener;
use App\Service\EtagService;
use App\Type\EtagType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[Small]
#[CoversClass(EtagControllerEventListener::class)]
class EtagControllerEventListenerTest extends TestCase
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

        $eventListener = new EtagControllerEventListener(
            $this->prophesize(EtagService::class)->reveal()
        );
        $eventListener->onKernelController($event);
    }

    public function testControllerWithEndpointSupportsEtagAttributeCallsEtagService(): void
    {
        $closure = #[EndpointSupportsEtag(EtagType::ELEMENT)]
        fn () => true;

        $request = $this->prophesize(Request::class)->reveal();

        $event = new ControllerEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $closure,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $etagService = $this->prophesize(EtagService::class);
        $etagService->setCurrentRequestEtagFromRequestAndEtagType(Argument::is($request), Argument::is(EtagType::ELEMENT))
            ->shouldBeCalledOnce()
            ->willReturn($etagService);

        $eventListener = new EtagControllerEventListener(
            $etagService->reveal()
        );
        $eventListener->onKernelController($event);
    }
}
