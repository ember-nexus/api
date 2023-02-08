<?php

namespace App\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class ElementResponse extends JsonResponse
{
    public function __construct(mixed $data = null, int $status = 200, array $headers = [], bool $json = false)
    {
        $this->charset = 'UTF-8';
        parent::__construct($data, $status, $headers, $json);
    }
}
