<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class Server500LogicErrorException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Internal server error',
        int $status = 500,
        string $detail = '',
        ?string $instance = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
