<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class Server503ServiceUnavailableException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Service unavailable',
        int $status = 503,
        string $detail = '',
        ?string $instance = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
