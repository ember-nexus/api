<?php

namespace App\Exception;

use Throwable;

class Client405MethodNotAllowedException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Method not allowed',
        int $status = 405,
        string $detail = 'Endpoint does not support requested method, or you do not have sufficient permissions.',
        ?string $instance = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
