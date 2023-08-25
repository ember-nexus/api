<?php

namespace App\Exception;

use Throwable;

class Client400IncompleteMutualDependencyException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Incomplete mutual dependency',
        int $status = 400,
        string $detail = '',
        string $instance = null,
        Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
