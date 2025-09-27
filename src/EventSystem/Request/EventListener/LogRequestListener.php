<?php

declare(strict_types=1);

namespace App\EventSystem\Request\EventListener;

use App\Security\AuthProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
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
        #[Autowire(service: 'router.default')]
        private UrlMatcherInterface|RequestMatcherInterface $matcher,
        private AuthProvider $authProvider,
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener]
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $this->request = $event->getRequest();
    }

    #[AsEventListener]
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
                    'user' => $this->authProvider->isAnonymous() ? 'anonymous' : $this->authProvider->getUserId()->toString(),
                    'token' => $this->authProvider->getTokenId()?->toString(),
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
