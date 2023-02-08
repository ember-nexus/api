<?php

namespace App\Exception;

class ClientTooManyRequestsException extends ClientException
{
    public function __construct(
        string $type = '429-too-many-requests',
        string $title = 'Too Many Requests',
        int $status = 429,
        string $detail = 'The client sent too many requests in a given timeframe; rate limiting is active.',
        ?string $instance = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
