<?php

declare(strict_types=1);

namespace App\EventSystem\Response\EventListener;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\RouterInterface;

use function Safe\file_put_contents;
use function Safe\rename;

/**
 * Adds the `Allow` header, listing the methods supported by the requested URL; since every method of a URL is its
 * own route, the methods are collected from all routes sharing the matched route's path. Requests without a
 * matching route (404) get no header here; a `405` carries its own `Allow` header from the router instead.
 *
 * Collecting the methods needs every route, and loading all routes is expensive, so the result is built while the
 * cache is warmed (a PHP file, opcache keeps it in memory) instead of on every request.
 */
class AllowHeaderResponseEventListener implements CacheWarmerInterface
{
    private const string CACHE_FILE = 'allow-headers.php';

    /**
     * @var array<string, string>|null Allow header value by route name
     */
    private ?array $allowHeaderByRoute = null;

    public function __construct(
        private RouterInterface $router,
        #[Autowire('%kernel.cache_dir%')]
        private string $cacheDir,
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
        $allowHeader = $this->getAllowHeaderByRoute()[$routeName] ?? null;
        if (null === $allowHeader) {
            return;
        }
        $response->headers->set('Allow', $allowHeader);
    }

    public function isOptional(): bool
    {
        return false;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     *
     * @return string[]
     */
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $file = $cacheDir.'/'.self::CACHE_FILE;
        $temporaryFile = $file.'.'.bin2hex(random_bytes(4)).'.tmp';
        file_put_contents($temporaryFile, '<?php return '.var_export($this->buildAllowHeaderByRoute(), true).';');
        rename($temporaryFile, $file);

        return [];
    }

    /**
     * @return array<string, string>
     */
    private function getAllowHeaderByRoute(): array
    {
        if (null !== $this->allowHeaderByRoute) {
            return $this->allowHeaderByRoute;
        }
        $file = $this->cacheDir.'/'.self::CACHE_FILE;
        if (!is_file($file)) {
            $this->warmUp($this->cacheDir);
        }
        /** @var array<string, string> $map */
        $map = require $file;

        return $this->allowHeaderByRoute = $map;
    }

    /**
     * @return array<string, string>
     */
    private function buildAllowHeaderByRoute(): array
    {
        /**
         * @var array<string, string[]|null> $methodsByPath null marks a path with a route which accepts any method
         */
        $methodsByPath = [];
        $pathByRoute = [];
        foreach ($this->router->getRouteCollection() as $name => $route) {
            $path = $route->getPath();
            $pathByRoute[$name] = $path;
            $methods = $route->getMethods();
            if (0 === count($methods) || (array_key_exists($path, $methodsByPath) && null === $methodsByPath[$path])) {
                $methodsByPath[$path] = null;
                continue;
            }
            $methodsByPath[$path] = [...($methodsByPath[$path] ?? []), ...$methods];
        }

        $allowHeaderByPath = [];
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
            $allowHeaderByPath[$path] = implode(', ', $methods);
        }

        $allowHeaderByRoute = [];
        foreach ($pathByRoute as $name => $path) {
            if (array_key_exists($path, $allowHeaderByPath)) {
                $allowHeaderByRoute[$name] = $allowHeaderByPath[$path];
            }
        }

        return $allowHeaderByRoute;
    }
}
