<?php

declare(strict_types=1);

namespace App\Service;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class RequestIdService
{
    private UuidInterface $requestId;

    public function __construct()
    {
        $this->requestId = Uuid::uuid4();
    }

    public function getRequestId(): UuidInterface
    {
        return $this->requestId;
    }
}
