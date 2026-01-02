<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class Client410GoneException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Gone',
        int $status = 410,
        string $detail = 'Requested resource is no longer available and is expected to soon be permanently deleted.',
        ?string $instance = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
