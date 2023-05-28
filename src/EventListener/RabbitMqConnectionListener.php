<?php

namespace App\EventListener;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMqConnectionListener
{
    private $connection;

    public function __construct(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;
    }

    public function onKernelTerminate(): void
    {
        $this->connection->close();
    }
}
