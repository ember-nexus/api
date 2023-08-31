<?php

namespace App\Exception;

use Throwable;

class Client403ForbiddenException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Requested endpoint, element or action is forbidden.',
        int $status = 403,
        string $detail = '',
        string $instance = null,
        Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
