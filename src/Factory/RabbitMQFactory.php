<?php

namespace App\Factory;

use App\Exception\ServerException;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMQFactory
{
    public function __construct(
        private string $rabbitMQAuth
    ) {
    }

    public function createRabbitMQ(): AMQPStreamConnection
    {
        $parsed = parse_url($this->rabbitMQAuth);

        if (!array_key_exists('user', $parsed)) {
            throw new ServerException(detail: 'RabbitMQ DSN requires user.');
        }
        $user = $parsed['user'];
        if (!$user) {
            throw new ServerException(detail: 'RabbitMQ DSN requires user.');
        }

        if (!array_key_exists('pass', $parsed)) {
            throw new ServerException(detail: 'RabbitMQ DSN requires password.');
        }
        $pass = $parsed['pass'];
        if (!$pass) {
            throw new ServerException(detail: 'RabbitMQ DSN requires password.');
        }

        if (!array_key_exists('host', $parsed)) {
            throw new ServerException(detail: 'RabbitMQ DSN requires host.');
        }
        $host = $parsed['host'];
        if (!$host) {
            throw new ServerException(detail: 'RabbitMQ DSN requires host.');
        }

        return new AMQPStreamConnection(
            $host,
            $parsed['port'] ?? 5672,
            $user,
            $pass
        );
    }
}
