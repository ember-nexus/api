<?php

declare(strict_types=1);

namespace App\EventSystem\Kernel\EventListener;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class RabbitMqConnectionListener
{
    private AMQPStreamConnection $connection;

    public function __construct(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;
    }

    #[AsEventListener('kernel.terminate')]
    public function onKernelTerminate(): void
    {
        $this->connection->close();
    }
}
