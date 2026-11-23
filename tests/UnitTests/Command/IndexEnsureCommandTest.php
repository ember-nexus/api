<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command;

use App\Command\IndexEnsureCommand;
use App\Service\Index\IndexEnsurer;
use App\Service\Index\IndexEnsureResult;
use App\Service\MaintenanceLockService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

#[Small]
#[CoversClass(IndexEnsureCommand::class)]
class IndexEnsureCommandTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $ensurer;

    private function tester(): CommandTester
    {
        $this->ensurer = $this->prophesize(IndexEnsurer::class);

        return new CommandTester(new IndexEnsureCommand($this->ensurer->reveal(), $this->prophesize(MaintenanceLockService::class)->reveal()));
    }

    public function testReportsCreatedIndices(): void
    {
        $tester = $this->tester();
        $this->ensurer->ensureWithLock(true, true, null, false, false, Argument::any())->willReturn(new IndexEnsureResult(['idx_new'], ['idx_old'], ['node X (index y)']));

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertStringContainsString('1 created', $tester->getDisplay());
        $this->assertStringContainsString('+ idx_new', $tester->getDisplay());
        $this->assertStringContainsString('= node X (index y)', $tester->getDisplay());
    }

    public function testOptionsArePassedThrough(): void
    {
        $tester = $this->tester();
        $this->ensurer->ensureWithLock(true, false, 30, true, false, Argument::any())->shouldBeCalledOnce()->willReturn(new IndexEnsureResult());

        $this->assertSame(Command::SUCCESS, $tester->execute(['--nodes-only' => true, '--wait' => '30', '--dry-run' => true]));
        $this->assertStringContainsString('would be created', $tester->getDisplay());
    }

    public function testRelationsOnlyAndWaitWithoutValue(): void
    {
        $tester = $this->tester();
        $this->ensurer->ensureWithLock(false, true, IndexEnsurer::DEFAULT_AWAIT_SECONDS, false, false, Argument::any())->shouldBeCalledOnce()->willReturn(new IndexEnsureResult());

        $this->assertSame(Command::SUCCESS, $tester->execute(['--relations-only' => true, '--wait' => null]));
    }

    public function testConflictingOptionsFail(): void
    {
        $tester = $this->tester();
        $this->ensurer->ensureWithLock(Argument::cetera())->shouldNotBeCalled();

        $this->assertSame(Command::FAILURE, $tester->execute(['--nodes-only' => true, '--relations-only' => true]));
    }

    public function testSkippedRunIsNoFailure(): void
    {
        $tester = $this->tester();
        $this->ensurer->ensureWithLock(Argument::cetera())->willReturn(null);

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertStringContainsString('skipped', $tester->getDisplay());
    }
}
