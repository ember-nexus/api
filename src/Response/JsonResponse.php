<?php

declare(strict_types=1);

namespace App\Response;

use App\Type\Etag;
use Symfony\Component\HttpFoundation\JsonResponse as SymfonyJsonResponse;

class JsonResponse extends SymfonyJsonResponse
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(mixed $data = null, int $status = 200, array $headers = [], bool $json = false)
    {
        $this->charset = 'UTF-8';
        $this->encodingOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        parent::__construct(
            $data,
            $status,
            [
                'Content-Type' => 'application/json; charset=utf-8',
                ...$headers,
            ],
            $json
        );
    }

    public function setEtagFromEtagInstance(Etag $etag): static
    {
        return parent::setEtag((string) $etag);
    }
}
