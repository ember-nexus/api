<?php

declare(strict_types=1);

namespace App\Command;

use App\Command\Cron\DeleteExpiredUploadsCommand;
use App\Command\Cron\ReindexFilesCommand;
use App\Command\Cron\UpdateOwnershipCommand;
use App\Service\CronExecutionGateService;
use App\Style\EmberNexusStyle;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Throwable;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'cron', description: 'Executes background tasks')]
class CronCommand extends Command
{
    private OutputStyle $io;

    public function __construct(
        private CronExecutionGateService $cronExecutionGateService,
        private DeleteExpiredUploadsCommand $deleteExpiredUploadsCommand,
        private ReindexFilesCommand $reindexFilesCommand,
        private UpdateOwnershipCommand $updateOwnershipCommand,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Cron');

        if ($this->cronExecutionGateService->shouldSkipExecution()) {
            $this->io->finalMessage('Cron is disabled; this command terminates early.');

            return Command::SUCCESS;
        }

        // a failing task must not prevent the following ones from running
        $hasFailedTask = false;
        foreach ([$this->deleteExpiredUploadsCommand, $this->reindexFilesCommand, $this->updateOwnershipCommand] as $taskCommand) {
            try {
                if (Command::SUCCESS !== $taskCommand->run(new ArrayInput([]), $output)) {
                    $hasFailedTask = true;
                }
            } catch (Throwable $throwable) {
                $hasFailedTask = true;
                $this->logger->error(sprintf(
                    "Cron task '%s' failed: %s",
                    $taskCommand->getName() ?? $taskCommand::class,
                    $throwable->getMessage()
                ), ['exception' => $throwable]);
                $this->io->writeln(sprintf('  <error>Task %s failed: %s</error>', $taskCommand->getName() ?? $taskCommand::class, $throwable->getMessage()));
            }
        }

        if ($hasFailedTask) {
            $this->io->finalMessage('Finished, but at least one task failed.');

            return Command::FAILURE;
        }
        $this->io->finalMessage('Finished.');

        return Command::SUCCESS;
    }
}
