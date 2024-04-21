<?php

declare(strict_types=1);

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method');
header('Access-Control-Allow-Methods: GET, HEAD, POST, OPTIONS, PUT, PATCH, DELETE, PROPFIND, PROPPATCH, MKCOL, COPY, MOVE, LOCK, UNLOCK');
header('Allow: GET, HEAD, POST, OPTIONS, PUT, PATCH, DELETE, PROPFIND, PROPPATCH, MKCOL, COPY, MOVE, LOCK, UNLOCK');
header('X-Powered-By: Ember Nexus API');
$method = $_SERVER['REQUEST_METHOD'];
if ('OPTIONS' == $method) {
    exit;
}

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
