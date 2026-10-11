<?php

declare(strict_types=1);

namespace App\EventSystem\Response\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Narrows the global `Access-Control-Allow-Methods` header down to the methods actually supported by the requested
 * URL, by copying the already-resolved `Allow` header set elsewhere for the same URL. Must run after
 * AllowHeaderResponseEventListener (hence the negative priority), since `Allow` needs to be resolved first.
 */
class CorsAllowMethodsResponseEventListener
{
    #[AsEventListener(priority: -10)]
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }
        $response = $event->getResponse();
        $allow = $response->headers->get('Allow');
        if (null === $allow) {
            return;
        }
        $response->headers->set('Access-Control-Allow-Methods', $allow);
    }
}
