<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class Client404NotFoundException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Not found',
        int $status = 404,
        string $detail = 'Requested element was not found.',
        ?string $instance = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
