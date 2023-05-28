<?php

namespace App\Exception;

class ClientBadIdException extends ClientException
{
    public function __construct(
        string $type = '400-bad-id',
        string $title = 'Bad Id',
        int $status = 400,
        string $detail = 'Requested UUID already exists in database.',
        string $instance = null,
        \Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
