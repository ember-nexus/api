<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\Index\IndexEnsurer;
use App\Service\MaintenanceLockService;
use App\Style\EmberNexusStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\pcntl_signal;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'index:ensure', description: 'Creates the id indices of all node labels and relationship types which do not have one yet.')]
class IndexEnsureCommand extends Command
{
    public function __construct(
        private IndexEnsurer $indexEnsurer,
        private MaintenanceLockService $maintenanceLockService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('nodes-only', null, InputOption::VALUE_NONE, 'Only create indices for node labels.')
            ->addOption('relations-only', null, InputOption::VALUE_NONE, 'Only create indices for relationship types.')
            ->addOption('wait', null, InputOption::VALUE_OPTIONAL, 'Wait until the new indices are online, at most the given number of seconds.', false)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only list the indices which would be created.');
    }

    /**
     * @SuppressWarnings("PHPMD.ExitExpression")
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new EmberNexusStyle($input, $output);
        if (function_exists('pcntl_async_signals')) {
            // free the lock right away on Ctrl+C / docker stop instead of waiting for its TTL
            pcntl_async_signals(true);
            $release = function (): never {
                $this->maintenanceLockService->releaseAllHeld();
                exit(1);
            };
            pcntl_signal(2, $release);
            pcntl_signal(15, $release);
        }
        $io->title('Index ensure');

        $nodesOnly = (bool) $input->getOption('nodes-only');
        $relationsOnly = (bool) $input->getOption('relations-only');
        if ($nodesOnly && $relationsOnly) {
            $io->error('The options --nodes-only and --relations-only exclude each other.');

            return Command::FAILURE;
        }
        /** @var string|false|null $wait */
        $wait = $input->getOption('wait');
        $awaitSeconds = false === $wait ? null : (null === $wait ? IndexEnsurer::DEFAULT_AWAIT_SECONDS : (int) $wait);

        $skipReason = null;
        $result = $this->indexEnsurer->ensureWithLock(
            nodes: !$relationsOnly,
            relations: !$nodesOnly,
            awaitSeconds: $awaitSeconds,
            dryRun: (bool) $input->getOption('dry-run'),
            skipReason: $skipReason,
        );
        if (null === $result) {
            $io->warning(sprintf('Index ensure was skipped: %s.', (string) $skipReason));

            return Command::SUCCESS;
        }

        $io->writeln(sprintf(
            '  Neo4j: <info>%d</info> %s, <info>%d</info> already existed, <info>%d</info> covered by an index under another name.',
            count($result->getCreated()),
            $input->getOption('dry-run') ? 'would be created' : 'created',
            count($result->getExisting()),
            count($result->getEquivalent()),
        ));
        foreach ($result->getCreated() as $name) {
            $io->writeln(sprintf('    + %s', $name));
        }
        foreach ($result->getEquivalent() as $description) {
            $io->writeln(sprintf('    = %s', $description));
        }
        $io->writeln('  MongoDB and Elasticsearch: nothing to do, the element id is the document identifier there.');
        $io->finalMessage('Done.');

        return Command::SUCCESS;
    }
}
