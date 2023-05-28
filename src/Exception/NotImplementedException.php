<?php

namespace App\Exception;

class NotImplementedException extends ClientException
{
    public function __construct(
        string $type = '404-not-implemented',
        string $title = 'Not Implemented',
        int $status = 404,
        string $detail = 'The requested resource is not implemented.',
        string $instance = null,
        \Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
