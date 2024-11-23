<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class Server501NotImplementedException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Not implemented',
        int $status = 501,
        string $detail = 'Endpoint is currently not implemented.',
        ?string $instance = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
