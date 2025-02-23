<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Service\RequestIdService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\UuidInterface;

#[Small]
#[CoversClass(RequestIdService::class)]
class RequestIdServiceTest extends TestCase
{
    public function testValidateTypeFromBody(): void
    {
        $requestIdService = new RequestIdService();
        $this->assertInstanceOf(UuidInterface::class, $requestIdService->getRequestId());
    }
}
