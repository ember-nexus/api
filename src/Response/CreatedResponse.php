<?php

namespace App\Response;

use Symfony\Component\HttpFoundation\Response;

class CreatedResponse extends Response
{
    public function __construct(
        ?string $content = '',
        int $status = self::HTTP_CREATED,
        array $headers = []
    ) {
        $this->charset = 'UTF-8';
        parent::__construct($content, $status, $headers);
    }
}
