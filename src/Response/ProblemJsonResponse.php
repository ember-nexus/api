<?php

namespace App\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class ProblemJsonResponse extends JsonResponse
{
    public function __construct(mixed $data = null, int $status = 500, array $headers = [], bool $json = false)
    {
        $this->charset = 'UTF-8';
        parent::__construct(
            $data,
            $status,
            [
                'Content-Type' => 'application/problem+json',
                ...$headers,
            ],
            $json
        );
    }
}
