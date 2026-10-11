<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Logger;

use App\Logger\TerminalConsoleHandler;
use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

#[Small]
#[CoversClass(TerminalConsoleHandler::class)]
class TerminalConsoleHandlerTest extends TestCase
{
    private function createRecord(Level $level): LogRecord
    {
        return new LogRecord(new DateTimeImmutable(), 'app', $level, 'some message');
    }

    private function startCommand(TerminalConsoleHandler $handler, BufferedOutput $output): void
    {
        $handler->onCommand(new ConsoleCommandEvent(new Command('some-command'), new ArrayInput([]), $output));
    }

    public function testHandlesNoticeAndAboveInTerminal(): void
    {
        $handler = new TerminalConsoleHandler(isTerminal: fn () => true);
        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL);
        $this->startCommand($handler, $output);

        $this->assertFalse($handler->isHandling($this->createRecord(Level::Debug)));
        $this->assertFalse($handler->isHandling($this->createRecord(Level::Info)));
        $this->assertTrue($handler->isHandling($this->createRecord(Level::Notice)));
        $this->assertTrue($handler->isHandling($this->createRecord(Level::Warning)));
        $this->assertTrue($handler->isHandling($this->createRecord(Level::Error)));
    }

    public function testHandledRecordsAreWrittenAndNotPassedOn(): void
    {
        $handler = new TerminalConsoleHandler(isTerminal: fn () => true);
        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL);
        $this->startCommand($handler, $output);

        $this->assertTrue($handler->handle($this->createRecord(Level::Error)));
        $this->assertStringContainsString('some message', $output->fetch());
    }

    public function testRecordsBelowTheLevelBubbleToOtherHandlers(): void
    {
        $handler = new TerminalConsoleHandler(isTerminal: fn () => true);
        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL);
        $this->startCommand($handler, $output);

        $this->assertFalse($handler->handle($this->createRecord(Level::Info)));
        $this->assertSame('', $output->fetch());
    }

    public function testVerbosityShowsMoreRecords(): void
    {
        $handler = new TerminalConsoleHandler(isTerminal: fn () => true);
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $this->startCommand($handler, $output);

        $this->assertTrue($handler->isHandling($this->createRecord(Level::Info)));
        $this->assertFalse($handler->isHandling($this->createRecord(Level::Debug)));
    }

    public function testQuietOnlyShowsErrors(): void
    {
        $handler = new TerminalConsoleHandler(isTerminal: fn () => true);
        $output = new BufferedOutput(OutputInterface::VERBOSITY_QUIET);
        $this->startCommand($handler, $output);

        $this->assertFalse($handler->isHandling($this->createRecord(Level::Warning)));
        $this->assertTrue($handler->isHandling($this->createRecord(Level::Error)));
    }

    public function testDoesNothingWithoutTerminal(): void
    {
        $handler = new TerminalConsoleHandler(isTerminal: fn () => false);
        $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $this->startCommand($handler, $output);

        $this->assertFalse($handler->isHandling($this->createRecord(Level::Emergency)));
        $this->assertFalse($handler->handle($this->createRecord(Level::Emergency)));
        $this->assertSame('', $output->fetch());
    }

    public function testDoesNothingOutsideOfCommands(): void
    {
        // regular requests never dispatch console events, so no output is ever set
        $handler = new TerminalConsoleHandler(isTerminal: fn () => true);

        $this->assertFalse($handler->isHandling($this->createRecord(Level::Emergency)));
        $this->assertFalse($handler->handle($this->createRecord(Level::Emergency)));
    }

    public function testStopsHandlingAfterTheCommandTerminated(): void
    {
        $handler = new TerminalConsoleHandler(isTerminal: fn () => true);
        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL);
        $this->startCommand($handler, $output);
        $this->assertTrue($handler->isHandling($this->createRecord(Level::Error)));

        $handler->onTerminate(new ConsoleTerminateEvent(new Command('some-command'), new ArrayInput([]), $output, 0));

        $this->assertFalse($handler->isHandling($this->createRecord(Level::Error)));
    }

    public function testSubscribesToConsoleEvents(): void
    {
        $events = TerminalConsoleHandler::getSubscribedEvents();

        $this->assertArrayHasKey(ConsoleEvents::COMMAND, $events);
        $this->assertArrayHasKey(ConsoleEvents::TERMINATE, $events);
    }

    public function testDefaultTerminalDetectionDoesNotFail(): void
    {
        // the phpunit process usually has no terminal on STDERR; only checks that the default detection works
        $handler = new TerminalConsoleHandler();
        $output = new BufferedOutput(OutputInterface::VERBOSITY_NORMAL);
        $this->startCommand($handler, $output);

        $this->assertIsBool($handler->isHandling($this->createRecord(Level::Error)));
    }
}
