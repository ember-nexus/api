<?php

namespace App\Exception;

use Throwable;

class Client400BadContentException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Bad content',
        int $status = 400,
        string $detail = '',
        ?string $instance = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
