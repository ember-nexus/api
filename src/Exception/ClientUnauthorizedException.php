<?php

namespace App\Exception;

class ClientUnauthorizedException extends ClientException
{
    public function __construct(
        string $type = '401-unauthorized',
        string $title = 'Unauthorized',
        int $status = 401,
        string $detail = 'Request requires authorization.',
        string $instance = null,
        \Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
