<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Service\CronTimeBudgetService;
use App\Service\TtyDetectorService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(CronTimeBudgetService::class)]
class CronTimeBudgetServiceTest extends TestCase
{
    use ProphecyTrait;

    protected function tearDown(): void
    {
        putenv('MAX_WORK_TIME_IN_S');
    }

    public function testGetDeadlineReturnsNullWhenInteractive(): void
    {
        $ttyDetectorService = $this->prophesize(TtyDetectorService::class);
        $ttyDetectorService->isInteractive()->willReturn(true);

        $service = new CronTimeBudgetService($ttyDetectorService->reveal());

        $this->assertNull($service->getDeadline());
    }

    public function testGetDeadlineUsesDefaultOf180SecondsWhenEnvIsNotSet(): void
    {
        putenv('MAX_WORK_TIME_IN_S');
        $ttyDetectorService = $this->prophesize(TtyDetectorService::class);
        $ttyDetectorService->isInteractive()->willReturn(false);

        $service = new CronTimeBudgetService($ttyDetectorService->reveal());

        $before = time();
        $deadline = $service->getDeadline();
        $after = time();

        $this->assertNotNull($deadline);
        $this->assertGreaterThanOrEqual($before + 180, $deadline);
        $this->assertLessThanOrEqual($after + 180, $deadline);
    }

    public function testGetDeadlineUsesMaxWorkTimeInSEnvVariableWhenSet(): void
    {
        putenv('MAX_WORK_TIME_IN_S=10');
        $ttyDetectorService = $this->prophesize(TtyDetectorService::class);
        $ttyDetectorService->isInteractive()->willReturn(false);

        $service = new CronTimeBudgetService($ttyDetectorService->reveal());

        $before = time();
        $deadline = $service->getDeadline();
        $after = time();

        $this->assertNotNull($deadline);
        $this->assertGreaterThanOrEqual($before + 10, $deadline);
        $this->assertLessThanOrEqual($after + 10, $deadline);
    }

    public function testGetDeadlineFallsBackToDefaultWhenEnvIsNotNumeric(): void
    {
        putenv('MAX_WORK_TIME_IN_S=not-a-number');
        $ttyDetectorService = $this->prophesize(TtyDetectorService::class);
        $ttyDetectorService->isInteractive()->willReturn(false);

        $service = new CronTimeBudgetService($ttyDetectorService->reveal());

        $before = time();
        $deadline = $service->getDeadline();

        $this->assertNotNull($deadline);
        $this->assertGreaterThanOrEqual($before + 180, $deadline);
    }

    public function testIsInteractiveDelegatesToTtyDetectorService(): void
    {
        $ttyDetectorService = $this->prophesize(TtyDetectorService::class);
        $ttyDetectorService->isInteractive()->willReturn(true);

        $service = new CronTimeBudgetService($ttyDetectorService->reveal());

        $this->assertTrue($service->isInteractive());
    }
}
