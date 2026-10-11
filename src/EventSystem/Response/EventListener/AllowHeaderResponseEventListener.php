<?php

declare(strict_types=1);

namespace App\EventSystem\Response\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\RouterInterface;

/**
 * Adds the `Allow` header, listing the methods supported by the requested URL; since every method of a URL is its
 * own route, the methods are collected from all routes sharing the matched route's path. Requests without a
 * matching route (404) get no header here; a `405` carries its own `Allow` header from the router instead.
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
            // every URL supports OPTIONS for the CORS preflight
            $methods[] = 'OPTIONS';
            $methods = array_values(array_unique($methods));
            sort($methods);
            $this->allowHeaderByPath[$path] = implode(', ', $methods);
        }

        return $this->allowHeaderByPath;
    }
}
