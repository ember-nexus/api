<?php

declare(strict_types=1);

namespace App\EventSystem\Kernel\EventListener;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\VarExporter\LazyObjectInterface;

class RabbitMqConnectionListener
{
    private AMQPStreamConnection $connection;

    public function __construct(AMQPStreamConnection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * The connection service is lazy, so most requests never actually open one; closing it unconditionally would
     * force it open just to immediately close it again. Only an already-initialized (i.e. actually used) connection
     * needs closing.
     */
    #[AsEventListener('kernel.terminate')]
    public function onKernelTerminate(): void
    {
        if ($this->connection instanceof LazyObjectInterface && !$this->connection->isLazyObjectInitialized()) {
            return;
        }
        $this->connection->close();
    }
}
