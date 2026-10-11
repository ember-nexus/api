<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Service\TtyDetectorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

/**
 * The real tty state of STDIN can not be controlled from within a PHPUnit process, so this only pins down that the
 * check is safe to call and returns a boolean. The actual interactive/non-interactive distinction is exercised
 * elsewhere, where this service is mocked.
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
