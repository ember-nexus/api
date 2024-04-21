<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class Client412PreconditionFailedException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Precondition Failed',
        int $status = 412,
        string $detail = 'Precondition does not match.',
        ?string $instance = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
