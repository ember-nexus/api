<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class Client400ReservedTypeException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Reserved type',
        int $status = 400,
        string $detail = '',
        ?string $instance = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
