<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class Client400ReservedIdentifierException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Reserved identifier',
        int $status = 400,
        string $detail = '',
        ?string $instance = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
