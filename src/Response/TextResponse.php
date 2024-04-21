<?php

declare(strict_types=1);

namespace App\Response;

use Symfony\Component\HttpFoundation\Response;

class TextResponse extends Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(string $content = '', int $status = 200, array $headers = [])
    {
        $this->charset = 'UTF-8';
        parent::__construct(
            $content,
            $status,
            [
                'Content-Type' => 'text/plain; charset=utf-8',
                ...$headers,
            ]
        );
    }
}
