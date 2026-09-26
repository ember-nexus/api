<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class Client408RequestTimeoutException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Request timeout',
        int $status = 408,
        string $detail = 'The request was not received completely.',
        ?string $instance = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
