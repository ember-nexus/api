<?php

namespace App\Exception;

class ClientForbiddenException extends ClientException
{
    public function __construct(
        string $type = '403-forbidden',
        string $title = 'Forbidden',
        int $status = 403,
        string $detail = 'Client does not have permissions to perform action.',
        ?string $instance = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($type, $title, $status, $detail, $instance, $previous);
    }
}
