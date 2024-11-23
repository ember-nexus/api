<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class Client403ForbiddenException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Forbidden',
        int $status = 403,
        string $detail = 'Requested endpoint, element or action is forbidden.',
        ?string $instance = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
