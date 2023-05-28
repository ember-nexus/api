<?php

namespace App\Exception;

class ServerException extends ExtendedException
{
    public function __construct(
        string $type = '500-generic-server-error',
        string $title = 'Generic server error',
        int $status = 500,
        string $detail = '',
        string $instance = null,
        \Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
