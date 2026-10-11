<?php

declare(strict_types=1);

namespace App\EventSystem\Request\EventListener;

use App\Security\AuthProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class LogRequestListener
{
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
        $response = $event->getResponse();

        $this->logger->info(
            'Handled request.',
            [
                'client' => [
                    'user' => $this->authProvider->isAnonymous() ? 'anonymous' : $this->authProvider->getUserId()->toString(),
                    'token' => $this->authProvider->getTokenId()?->toString(),
                    'ip' => $request->getClientIp(),
                ],
                'request' => [
                    // the route-matching routers's own listener already resolved `_route` on kernel.request; a
                    // request without a matching route (404) or which never reached routing (e.g. rejected by
                    // ApiKeyCheckOnKernelRequestEventListener before RouterListener ran) simply has none
                    'route' => $request->attributes->get('_route', 'n/a'),
                    'uri' => $request->getUri(),
                    'method' => $request->getMethod(),
                    'type' => $request->getContentTypeFormat(),
                ],
                'response' => [
                    'status' => $response->getStatusCode(),
                    'type' => $response->headers->get('content-type'),
                ],
            ]
        );
    }
}
