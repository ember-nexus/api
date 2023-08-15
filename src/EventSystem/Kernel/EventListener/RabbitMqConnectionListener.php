<?php

namespace App\EventSystem\Kernel\EventListener;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMqConnectionListener
{
    private AMQPStreamConnection $connection;

    public function __construct(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;
    }

    public function onKernelTerminate(): void
    {
        $this->connection->close();
    }
}
