<?php

namespace App\Exception;

use Throwable;

class Client401UnauthorizedException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Request does not contain valid token, or anonymous user is disabled.',
        int $status = 401,
        string $detail = '',
        string $instance = null,
        Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
