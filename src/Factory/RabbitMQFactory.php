<?php

namespace App\Factory;

use App\Factory\Exception\Server500LogicExceptionFactory;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQFactory
{
    public function __construct(
        private string $rabbitMQAuth,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory
    ) {
    }

    public function createRabbitMQ(): AMQPStreamConnection
    {
        $parsed = parse_url($this->rabbitMQAuth);

        if (!is_array($parsed)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('Unable to correctly parse RabbitMQ DSN.');
        }

        if (!array_key_exists('user', $parsed)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('RabbitMQ DSN requires user.');
        }
        $user = $parsed['user'];
        if (!$user) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('RabbitMQ DSN requires user.');
        }

        if (!array_key_exists('pass', $parsed)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('RabbitMQ DSN requires password.');
        }
        $pass = $parsed['pass'];
        if (!$pass) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('RabbitMQ DSN requires password.');
        }

        if (!array_key_exists('host', $parsed)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('RabbitMQ DSN requires host.');
        }
        $host = $parsed['host'];
        if (!$host) {
            throw $this->server500LogicExceptionFactory->createFromTemplate('RabbitMQ DSN requires host.');
        }

        return new AMQPStreamConnection(
            $host,
            $parsed['port'] ?? 5672,
            $user,
            $pass
        );
    }
}
