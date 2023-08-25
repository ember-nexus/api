<?php

namespace App\Exception;

use Throwable;

class Client429TooManyRequestsException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Too many requests',
        int $status = 429,
        string $detail = 'You have sent too many requests, please slow down.',
        string $instance = null,
        Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
