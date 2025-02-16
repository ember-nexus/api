<?php

declare(strict_types=1);

namespace App\Response;

use Symfony\Component\HttpFoundation\Response;

class CreatedResponse extends Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        ?string $location = '',
        int $status = self::HTTP_CREATED,
        array $headers = [],
    ) {
        $this->charset = 'UTF-8';
        $headers['Location'] = $location;
        parent::__construct(null, $status, $headers);
    }
}
