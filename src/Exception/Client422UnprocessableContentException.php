<?php

declare(strict_types=1);

namespace App\Exception;

use Throwable;

class Client422UnprocessableContentException extends ProblemJsonException
{
    public function __construct(
        string $type,
        string $title = 'Unprocessable content',
        int $status = 422,
        string $detail = 'The request content was understood, but could not be processed.',
        ?string $instance = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
