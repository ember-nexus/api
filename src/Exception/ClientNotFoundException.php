<?php

namespace App\Exception;

class ClientNotFoundException extends ClientException
{
    public function __construct(
        string $type = '404-not-found',
        string $title = 'Not Found',
        int $status = 404,
        string $detail = 'The requested resource was not found.',
        ?string $instance = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
