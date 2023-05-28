<?php

namespace App\Exception;

class ClientException extends ExtendedException
{
    public function __construct(
        string $type,
        string $title = 'Generic client error',
        int $status = 400,
        string $detail = '',
        string $instance = null,
        \Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
