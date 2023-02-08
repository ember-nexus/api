<?php

namespace App\Response;

use Symfony\Component\HttpFoundation\Response;

class NoContentResponse extends Response
{
    public function __construct(
        ?string $content = '',
        int $status = self::HTTP_NO_CONTENT,
        array $headers = []
    ) {
        $this->charset = 'UTF-8';
        parent::__construct($content, $status, $headers);
    }
}
