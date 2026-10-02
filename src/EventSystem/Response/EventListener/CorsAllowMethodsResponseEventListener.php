<?php

declare(strict_types=1);

namespace App\EventSystem\Response\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Narrows the `Access-Control-Allow-Methods` header, set to the global list of all methods used by the API in
 * public/index.php, down to the methods actually supported by the requested URL, by mirroring the `Allow` header:
 * see AllowHeaderResponseEventListener for the regular case, and NoRouteFoundExceptionEventListener for the `405`
 * case, where `Allow` is populated from the router's own exception instead of a matched route. Both describe the
 * exact same set of methods, so copying the already-resolved value is all that's needed; there is no route lookup
 * or other computation to do here, so this listener costs nothing beyond the header copy itself.
 *
 * Must run after AllowHeaderResponseEventListener, hence the explicit negative priority: `Allow` has to be resolved
 * first for there to be anything to mirror.
 *
 * Requests without a matching route (404) or the CORS preflight (answered directly by public/index.php, before
 * Symfony boots, and thus never carrying an `Allow` header to mirror) keep the global list set by public/index.php.
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
