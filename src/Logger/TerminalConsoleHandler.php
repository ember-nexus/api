<?php

declare(strict_types=1);

namespace App\Logger;

use Closure;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Writes log records of console commands to the terminal, but only if the commands run in one. Without a terminal
 * (cron, CI, pipes) the records are left to the JSON handlers, and regular requests never activate this handler, as
 * they do not dispatch console events.
 *
 * By default records with the level notice and above are shown, the verbosity options of the command (`-v`, `-vv`,
 * `-vvv`) show more. The handler does not bubble, so records it wrote are not written a second time by other handlers.
 */
final class TerminalConsoleHandler implements HandlerInterface, EventSubscriberInterface
{
    private ConsoleHandler $consoleHandler;
    /**
     * @var Closure(): bool
     */
    private Closure $isTerminal;

    /**
     * @param array<int, Level>      $verbosityLevelMap
     * @param (Closure(): bool)|null $isTerminal
     */
    public function __construct(array $verbosityLevelMap = [], ?Closure $isTerminal = null)
    {
        $this->consoleHandler = new ConsoleHandler(
            null,
            false,
            [] !== $verbosityLevelMap ? $verbosityLevelMap : [
                OutputInterface::VERBOSITY_QUIET => Level::Error,
                OutputInterface::VERBOSITY_NORMAL => Level::Notice,
                OutputInterface::VERBOSITY_VERBOSE => Level::Notice,
                OutputInterface::VERBOSITY_VERY_VERBOSE => Level::Info,
                OutputInterface::VERBOSITY_DEBUG => Level::Debug,
            ]
        );
        // false means that there is no terminal, which is a valid result and not an error as the Safe variant expects
        $this->isTerminal = $isTerminal ?? static fn (): bool => defined('STDERR') && stream_isatty(STDERR); // @phpstan-ignore theCodingMachineSafe.function
    }

    public function isHandling(LogRecord $record): bool
    {
        return $this->consoleHandler->isHandling($record);
    }

    public function handle(LogRecord $record): bool
    {
        return $this->consoleHandler->handle($record);
    }

    public function handleBatch(array $records): void
    {
        foreach ($records as $record) {
            $this->handle($record);
        }
    }

    public function close(): void
    {
        $this->consoleHandler->close();
    }

    public function onCommand(ConsoleCommandEvent $event): void
    {
        if (!($this->isTerminal)()) {
            return;
        }

        $this->consoleHandler->onCommand($event);
    }

    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        $this->consoleHandler->onTerminate($event);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => ['onCommand', 255],
            ConsoleEvents::TERMINATE => ['onTerminate', -255],
        ];
    }
}
