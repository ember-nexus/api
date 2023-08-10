<?php

namespace App\Response;

class ProblemJsonResponse extends JsonResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(mixed $data = null, int $status = 500, array $headers = [], bool $json = false)
    {
        parent::__construct(
            $data,
            $status,
            [
                'Content-Type' => 'application/problem+json; charset=utf-8',
                ...$headers,
            ],
            $json
        );
    }
}
