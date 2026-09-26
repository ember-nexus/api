<?php

declare(strict_types=1);

namespace App\Command;

use App\Command\Cron\DeleteExpiredUploadsCommand;
use App\Command\Cron\ReindexFilesCommand;
use App\Style\EmberNexusStyle;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Throwable;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'cron', description: 'Executes background tasks')]
class CronCommand extends Command
{
    private OutputStyle $io;

    public function __construct(
        private ParameterBagInterface $bag,
        private DeleteExpiredUploadsCommand $deleteExpiredUploadsCommand,
        private ReindexFilesCommand $reindexFilesCommand,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Cron');

        $isCronDisabled = $this->bag->get('isCronDisabled');
        if (!is_bool($isCronDisabled)) {
            throw new LogicException(sprintf('Expected "isCronDisabled" to be of type boolean, got %s.', get_debug_type($isCronDisabled)));
        }
        if ($isCronDisabled) {
            $this->io->finalMessage('Cron is disabled; this command terminates early.');

            return Command::SUCCESS;
        }

        // Note: cron:update-ownership is intentionally not dispatched here yet, see
        // https://github.com/ember-nexus/api/issues/438.

        // a failing task must not prevent the following ones from running
        $hasFailedTask = false;
        foreach ([$this->deleteExpiredUploadsCommand, $this->reindexFilesCommand] as $taskCommand) {
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
