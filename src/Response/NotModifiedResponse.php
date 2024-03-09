<?php

namespace App\Response;

use App\Type\Etag;
use Symfony\Component\HttpFoundation\Response;

class NotModifiedResponse extends Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        ?string $content = '',
        int $status = self::HTTP_NOT_MODIFIED,
        array $headers = []
    ) {
        $this->charset = 'UTF-8';
        parent::__construct($content, $status, $headers);
    }

    public function setEtagFromEtagInstance(Etag $etag): static
    {
        return parent::setEtag((string) $etag);
    }
}
