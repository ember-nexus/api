<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command;

use App\Command\MphfBuildCommand;
use App\Service\MaintenanceLockService;
use App\Service\Mphf\MphfBuildResult;
use App\Service\Mphf\MphfBuildService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[Small]
#[CoversClass(MphfBuildCommand::class)]
class MphfBuildCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $service;

    private function tester(): CommandTester
    {
        $this->service = $this->prophesize(MphfBuildService::class);

        return new CommandTester(new MphfBuildCommand($this->service->reveal(), $this->prophesize(MaintenanceLockService::class)->reveal()));
    }

    public function testSuccessfulBuildIsSummarized(): void
    {
        $tester = $this->tester();
        $this->service->build(Argument::any())->will(function (array $args) {
            $args[0]('working');

            return new MphfBuildResult(false, null, '20260101-0317.mphf', 1234, 5000, 1.5, 2);
        });

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $display = $tester->getDisplay();
        $this->assertStringContainsString('working', $display);
        $this->assertStringContainsString('20260101-0317.mphf', $display);
        $this->assertStringContainsString('1234 ids', $display);
        $this->assertStringContainsString('2 old file(s) deleted', $display);
    }

    public function testSkippedBuildExitsWithSuccess(): void
    {
        $tester = $this->tester();
        $this->service->build(Argument::any())->willReturn(MphfBuildResult::skipped('a build is running already'));

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertStringContainsString('a build is running already', $tester->getDisplay());
    }

    public function testFailureExitsWithFailure(): void
    {
        $tester = $this->tester();
        $this->service->build(Argument::any())->willThrow(new RuntimeException('builder exploded'));

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $this->assertStringContainsString('builder exploded', $tester->getDisplay());
    }
}
