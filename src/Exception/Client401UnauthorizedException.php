<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class Client401UnauthorizedException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Unauthorized',
        int $status = 401,
        string $detail = 'Authorization for the request failed due to possible problems with the token (incorrect or expired), password (incorrect or changed), the user\'s unique identifier, or the user\'s status (e.g., missing, blocked, or deleted).',
        ?string $instance = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
