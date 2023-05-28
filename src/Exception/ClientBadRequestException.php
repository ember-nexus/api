<?php

namespace App\Exception;

class ClientBadRequestException extends ClientException
{
    public function __construct(
        string $type = '400-bad-request',
        string $title = 'Bad Request',
        int $status = 400,
        string $detail = 'Request can not be handled due to malformed content.',
        string $instance = null,
        \Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
