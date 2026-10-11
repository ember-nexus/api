<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Service\CronExecutionGateService;
use App\Service\TtyDetectorService;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Small]
#[CoversClass(CronExecutionGateService::class)]
class CronExecutionGateServiceTest extends TestCase
{
    use ProphecyTrait;

    public function testExecutionIsNotSkippedWhenCronIsEnabledRegardlessOfTty(): void
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isCronDisabled')->willReturn(false);

        $ttyDetectorService = $this->prophesize(TtyDetectorService::class);
        $ttyDetectorService->isInteractive()->shouldNotBeCalled();

        $service = new CronExecutionGateService($bag->reveal(), $ttyDetectorService->reveal());

        $this->assertFalse($service->shouldSkipExecution());
    }

    public function testExecutionIsSkippedWhenCronIsDisabledAndNotInteractive(): void
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isCronDisabled')->willReturn(true);

        $ttyDetectorService = $this->prophesize(TtyDetectorService::class);
        $ttyDetectorService->isInteractive()->willReturn(false);

        $service = new CronExecutionGateService($bag->reveal(), $ttyDetectorService->reveal());

        $this->assertTrue($service->shouldSkipExecution());
    }

    public function testExecutionIsNotSkippedWhenCronIsDisabledButInteractive(): void
    {
        // a developer running a cron command by hand in a real terminal always executes it, regardless of
        // DISABLE_CRON
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isCronDisabled')->willReturn(true);

        $ttyDetectorService = $this->prophesize(TtyDetectorService::class);
        $ttyDetectorService->isInteractive()->willReturn(true);

        $service = new CronExecutionGateService($bag->reveal(), $ttyDetectorService->reveal());

        $this->assertFalse($service->shouldSkipExecution());
    }

    public function testThrowsIfCronDisabledParameterIsNotBoolean(): void
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get('isCronDisabled')->willReturn('not-a-boolean');

        $ttyDetectorService = $this->prophesize(TtyDetectorService::class);
        $ttyDetectorService->isInteractive()->shouldNotBeCalled();

        $service = new CronExecutionGateService($bag->reveal(), $ttyDetectorService->reveal());

        $this->expectException(LogicException::class);
        $service->shouldSkipExecution();
    }
}
