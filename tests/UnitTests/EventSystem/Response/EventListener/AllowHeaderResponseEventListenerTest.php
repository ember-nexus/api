<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Response\EventListener;

use App\EventSystem\Response\EventListener\AllowHeaderResponseEventListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;

#[Small]
#[CoversClass(AllowHeaderResponseEventListener::class)]
class AllowHeaderResponseEventListenerTest extends TestCase
{
    use ProphecyTrait;

    private function createListener(): AllowHeaderResponseEventListener
    {
        $routes = new RouteCollection();
        $routes->add('get-element', new Route('/{id}', methods: ['GET']));
        $routes->add('patch-element', new Route('/{id}', methods: ['PATCH']));
        $routes->add('delete-element', new Route('/{id}', methods: ['DELETE', 'put']));
        $routes->add('post-register', new Route('/register', methods: ['POST']));
        $routes->add('any-method', new Route('/anything'));
        $routes->add('any-method-with-restriction', new Route('/anything', methods: ['GET']));

        $router = $this->prophesize(RouterInterface::class);
        $router->getRouteCollection()->willReturn($routes);

        return new AllowHeaderResponseEventListener($router->reveal());
    }

    private function createEvent(?string $routeName, Response $response, int $requestType = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        $request = new Request();
        if (null !== $routeName) {
            $request->attributes->set('_route', $routeName);
        }

        return new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            $requestType,
            $response
        );
    }

    public function testMethodsOfAllRoutesWithTheSamePathAreListed(): void
    {
        $response = new Response();
        $this->createListener()->onKernelResponse($this->createEvent('patch-element', $response));

        $this->assertSame('DELETE, GET, HEAD, OPTIONS, PATCH, PUT', $response->headers->get('Allow'));
    }

    public function testHeadIsOnlyAddedIfGetIsAllowed(): void
    {
        $response = new Response();
        $this->createListener()->onKernelResponse($this->createEvent('post-register', $response));

        $this->assertSame('OPTIONS, POST', $response->headers->get('Allow'));
    }

    public function testErrorResponseOfMatchedRouteGetsHeader(): void
    {
        $response = new Response('', 404);
        $this->createListener()->onKernelResponse($this->createEvent('get-element', $response));

        $this->assertSame('DELETE, GET, HEAD, OPTIONS, PATCH, PUT', $response->headers->get('Allow'));
    }

    public function testRequestWithoutMatchedRouteGetsNoHeader(): void
    {
        $response = new Response();
        $this->createListener()->onKernelResponse($this->createEvent(null, $response));

        $this->assertFalse($response->headers->has('Allow'));
    }

    public function testUnknownRouteNameGetsNoHeader(): void
    {
        $response = new Response();
        $this->createListener()->onKernelResponse($this->createEvent('does-not-exist', $response));

        $this->assertFalse($response->headers->has('Allow'));
    }

    public function testSubRequestGetsNoHeader(): void
    {
        $response = new Response();
        $this->createListener()->onKernelResponse($this->createEvent('get-element', $response, HttpKernelInterface::SUB_REQUEST));

        $this->assertFalse($response->headers->has('Allow'));
    }

    public function testExistingHeaderIsKept(): void
    {
        $response = new Response('', 405, ['Allow' => 'POST']);
        $this->createListener()->onKernelResponse($this->createEvent('get-element', $response));

        $this->assertSame('POST', $response->headers->get('Allow'));
    }

    public function testPathWithRouteWhichAcceptsAnyMethodGetsNoHeader(): void
    {
        $response = new Response();
        $this->createListener()->onKernelResponse($this->createEvent('any-method-with-restriction', $response));

        $this->assertFalse($response->headers->has('Allow'));
    }
}
