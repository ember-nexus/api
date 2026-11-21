<?php

declare(strict_types=1);

namespace App\Factory;

use Predis\Client;
use Predis\Connection\Parameters;

class RedisFactory
{
    public function __construct(
        private string $redisAuth,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public function createRedis(): Client
    {
        // PHP threads of FrankenPHP keep persistent sockets between requests, so a request does not have to open a new
        // TCP connection to Redis
        $parameters = Parameters::parse($this->redisAuth);
        $parameters['persistent'] = true;
        // CLIENT SETINFO would be two more round trips on every request, only to tell the server the client library
        $parameters['client_info'] = false;

        return new Client($parameters);
    }
}
