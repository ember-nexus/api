<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Service;

use App\Service\RequestIdService;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;

class RequestIdServiceTest extends TestCase
{
    public function testValidateTypeFromBody(): void
    {
        $requestIdService = new RequestIdService();
        $this->assertInstanceOf(UuidInterface::class, $requestIdService->getRequestId());
    }
}
