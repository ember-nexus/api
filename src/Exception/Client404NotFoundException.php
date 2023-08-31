<?php

namespace App\Exception;

use Throwable;

class Client404NotFoundException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Requested element was not found.',
        int $status = 404,
        string $detail = '',
        string $instance = null,
        Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
