<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Request\EventListener;

use App\EventSystem\Request\EventListener\AuditUserAndTokenEndpointEventListener;
use App\Security\AuthProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[Small]
#[CoversClass(AuditUserAndTokenEndpointEventListener::class)]
class AuditUserAndTokenEndpointEventListenerTest extends TestCase
{
    use ProphecyTrait;

    private function buildListener(LoggerInterface $logger, bool $isAnonymous = true): AuditUserAndTokenEndpointEventListener
    {
        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->isAnonymous()->willReturn($isAnonymous);
        if (!$isAnonymous) {
            $authProvider->getUserId()->willReturn(Uuid::fromString('55555555-5555-5555-5555-555555555555'));
        }
        $authProvider->getTokenId()->willReturn(Uuid::fromString('66666666-6666-6666-6666-666666666666'));

        return new AuditUserAndTokenEndpointEventListener($authProvider->reveal(), $logger);
    }

    private function buildEvent(?string $route, bool $isMainRequest = true, int $status = 200): ResponseEvent
    {
        $kernel = $this->prophesize(HttpKernelInterface::class)->reveal();
        $request = Request::create('/whatever');
        if (null !== $route) {
            $request->attributes->set('_route', $route);
        }

        return new ResponseEvent(
            $kernel,
            $request,
            $isMainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST,
            new Response(status: $status)
        );
    }

    /**
     * @return array<string, array{string}>
     */
    public static function auditedRoutesProvider(): array
    {
        return [
            'post-register' => ['post-register'],
            'post-token' => ['post-token'],
            'get-token' => ['get-token'],
            'delete-token' => ['delete-token'],
            'post-change-password' => ['post-change-password'],
            'get-me' => ['get-me'],
        ];
    }

    #[DataProvider('auditedRoutesProvider')]
    public function testAuditedRouteIsLogged(string $route): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(
            'User/token endpoint was called.',
            Argument::that(fn ($context) => $route === $context['request']['route'])
        )->shouldBeCalledOnce();

        $listener = $this->buildListener($logger->reveal());
        $listener->onKernelResponse($this->buildEvent($route));
    }

    public function testLoggedPayloadCarriesClientAndResponseInformation(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(
            'User/token endpoint was called.',
            [
                'client' => [
                    'user' => '55555555-5555-5555-5555-555555555555',
                    'token' => '66666666-6666-6666-6666-666666666666',
                    'ip' => '127.0.0.1',
                ],
                'request' => [
                    'route' => 'get-me',
                    'method' => 'GET',
                ],
                'response' => [
                    'status' => 403,
                ],
            ]
        )->shouldBeCalledOnce();

        $listener = $this->buildListener($logger->reveal(), isAnonymous: false);
        $listener->onKernelResponse($this->buildEvent('get-me', status: 403));
    }

    public function testAnonymousUserIsLoggedAsAnonymous(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(
            'User/token endpoint was called.',
            Argument::that(fn ($context) => 'anonymous' === $context['client']['user'])
        )->shouldBeCalledOnce();

        $listener = $this->buildListener($logger->reveal(), isAnonymous: true);
        $listener->onKernelResponse($this->buildEvent('post-register'));
    }

    public function testNonAuditedRouteIsIgnored(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($logger->reveal());
        $listener->onKernelResponse($this->buildEvent('get-element'));
    }

    public function testRouteLessRequestIsIgnored(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($logger->reveal());
        $listener->onKernelResponse($this->buildEvent(null));
    }

    public function testSubRequestIsIgnored(): void
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->notice(Argument::cetera())->shouldNotBeCalled();

        $listener = $this->buildListener($logger->reveal());
        $listener->onKernelResponse($this->buildEvent('get-me', isMainRequest: false));
    }
}
