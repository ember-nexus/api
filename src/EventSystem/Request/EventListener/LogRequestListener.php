<?php

namespace App\EventSystem\Request\EventListener;

use App\Security\AuthProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;

class LogRequestListener
{
    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private Request $request;

    public function __construct(
        private UrlMatcherInterface|RequestMatcherInterface $matcher,
        private AuthProvider $authProvider,
        private LoggerInterface $logger
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $this->request = $event->getRequest();
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        if ($this->matcher instanceof RequestMatcherInterface) {
            $parameters = $this->matcher->matchRequest($this->request);
        } else {
            $parameters = $this->matcher->match($this->request->getPathInfo());
        }

        $this->logger->info(
            'Handled request.',
            [
                'client' => [
                    'user' => $this->authProvider->isAnonymous() ? 'anonymous' : $this->authProvider->getUserUuid(),
                    'token' => $this->authProvider->getTokenUuid(),
                    'ip' => $this->request->getClientIp(),
                ],
                'request' => [
                    'route' => $parameters['_route'] ?? 'n/a',
                    'uri' => $this->request->getUri(),
                    'method' => $this->request->getMethod(),
                    'type' => $this->request->getContentTypeFormat(),
                ],
                'response' => [
                    'status' => $response->getStatusCode(),
                    'type' => $response->headers->get('content-type'),
                ],
            ]
        );
    }
}
