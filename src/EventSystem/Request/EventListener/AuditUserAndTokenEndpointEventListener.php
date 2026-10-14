<?php

declare(strict_types=1);

namespace App\EventSystem\Request\EventListener;

use App\Security\AuthProvider;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Logs every call to a user/token management endpoint (register, login, password change, token lifecycle) on the
 * dedicated, always-flushed `audit` channel instead of regular request logging (see monolog.yaml).
 */
#[WithMonologChannel('audit')]
class AuditUserAndTokenEndpointEventListener
{
    /**
     * @var string[]
     */
    private const array AUDITED_ROUTES = [
        'post-register',
        'post-token',
        'get-token',
        'delete-token',
        'post-change-password',
        'get-me',
    ];

    public function __construct(
        private AuthProvider $authProvider,
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener]
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        if (!in_array($route, self::AUDITED_ROUTES, true)) {
            return;
        }

        $this->logger->notice(
            'User/token endpoint was called.',
            [
                'client' => [
                    'user' => $this->authProvider->isAnonymous() ? 'anonymous' : $this->authProvider->getUserId()->toString(),
                    'token' => $this->authProvider->getTokenId()?->toString(),
                    'ip' => $request->getClientIp(),
                ],
                'request' => [
                    'route' => $route,
                    'method' => $request->getMethod(),
                ],
                'response' => [
                    'status' => $event->getResponse()->getStatusCode(),
                ],
            ]
        );
    }
}
