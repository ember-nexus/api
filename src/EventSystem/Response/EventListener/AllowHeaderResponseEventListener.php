<?php

declare(strict_types=1);

namespace App\EventSystem\Response\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\RouterInterface;

/**
 * Adds the `Allow` header, which lists the methods supported by the requested URL. Every method of an URL is its own
 * route, so the methods are collected from all routes which share the path of the matched route.
 *
 * Requests without a matching route (404) get no header; the `405` response carries the header of the router, see
 * NoRouteFoundExceptionEventListener.
 */
class AllowHeaderResponseEventListener
{
    /**
     * @var array<string, string>|null Allow header value by route path
     */
    private ?array $allowHeaderByPath = null;

    public function __construct(
        private RouterInterface $router,
    ) {
    }

    #[AsEventListener]
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $response = $event->getResponse();
        if ($response->headers->has('Allow')) {
            return;
        }
        $routeName = $event->getRequest()->attributes->get('_route');
        if (!is_string($routeName)) {
            return;
        }
        $route = $this->router->getRouteCollection()->get($routeName);
        if (null === $route) {
            return;
        }
        $allowHeader = $this->getAllowHeaderByPath()[$route->getPath()] ?? null;
        if (null === $allowHeader) {
            return;
        }
        $response->headers->set('Allow', $allowHeader);
    }

    /**
     * @return array<string, string>
     */
    private function getAllowHeaderByPath(): array
    {
        if (null !== $this->allowHeaderByPath) {
            return $this->allowHeaderByPath;
        }

        /**
         * @var array<string, string[]|null> $methodsByPath null marks a path with a route which accepts any method
         */
        $methodsByPath = [];
        foreach ($this->router->getRouteCollection() as $route) {
            $path = $route->getPath();
            $methods = $route->getMethods();
            if (0 === count($methods) || (array_key_exists($path, $methodsByPath) && null === $methodsByPath[$path])) {
                $methodsByPath[$path] = null;
                continue;
            }
            $methodsByPath[$path] = [...($methodsByPath[$path] ?? []), ...$methods];
        }

        $this->allowHeaderByPath = [];
        foreach ($methodsByPath as $path => $methods) {
            if (null === $methods) {
                continue;
            }
            $methods = array_map('strtoupper', $methods);
            if (in_array('GET', $methods, true)) {
                // Symfony matches HEAD requests to routes of GET
                $methods[] = 'HEAD';
            }
            // the preflight of every URL is answered by public/index.php
            $methods[] = 'OPTIONS';
            $methods = array_values(array_unique($methods));
            sort($methods);
            $this->allowHeaderByPath[$path] = implode(', ', $methods);
        }

        return $this->allowHeaderByPath;
    }
}
