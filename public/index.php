<?php

declare(strict_types=1);

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// keep in sync with the snippet `problem_json_headers` in docker/Caddyfile; the `Allow` header depends on the route,
// see AllowHeaderResponseEventListener
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
header('Access-Control-Expose-Headers: ETag, Location');
header('X-Powered-By: Ember Nexus API');
$method = $_SERVER['REQUEST_METHOD'];
if ('OPTIONS' == $method) {
    // the preflight is answered here, before Symfony boots and can narrow this per route, see
    // CorsAllowMethodsResponseEventListener; Symfony response headers are sent with `replace: false` for anything
    // but `Content-Type` (see Response::sendHeaders()), so setting this header here for non-OPTIONS requests too
    // would just add a second, stale value instead of being overridden by the listener
    header('Access-Control-Allow-Methods: GET, HEAD, POST, OPTIONS, PUT, PATCH, DELETE');
    exit;
}

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
