<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Logger;

use App\Logger\RequestProcessor;
use App\Service\RequestIdService;
use App\Type\Uuid;
use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(RequestProcessor::class)]
#[AllowMockObjectsWithoutExpectations]
class RequestProcessorTest extends TestCase
{
    public function testRequestProcessor(): void
    {
        $requestIdService = $this->createMock(RequestIdService::class);
        $requestIdService->method('getRequestId')->willReturn(
            Uuid::fromString('02IiNr0G8viOVKjn71oeH0BK5KarxzLjlnY4f')
        );

        $requestProcessor = new RequestProcessor($requestIdService);

        $logRecord = new LogRecord(
            new DateTimeImmutable(),
            'channel',
            Level::Debug,
            'message'
        );

        $newLogRecord = $requestProcessor->__invoke($logRecord);
        $this->assertSame($logRecord, $newLogRecord);
        $this->assertArrayHasKey('requestId', $logRecord->extra);
        $this->assertSame('02IiNr0G8viOVKjn71oeH0BK5KarxzLjlnY4f', $logRecord->extra['requestId']);
    }
}
