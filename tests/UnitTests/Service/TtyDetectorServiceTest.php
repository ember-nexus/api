<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Service\TtyDetectorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

/**
 * The real tty state of STDIN can not be controlled from within a PHPUnit process, so this only pins down that the
 * check is safe to call and returns a boolean; the actual interactive/non-interactive distinction is exercised by
 * `CronExecutionGateServiceTest` (which mocks this service) and, for the real behaviour, manually against a
 * running container (see the feature test suite's cron CI wiring).
 */
#[Small]
#[CoversClass(TtyDetectorService::class)]
class TtyDetectorServiceTest extends TestCase
{
    public function testIsInteractiveReturnsBooleanWithoutError(): void
    {
        $service = new TtyDetectorService();

        $this->assertIsBool($service->isInteractive());
    }
}
