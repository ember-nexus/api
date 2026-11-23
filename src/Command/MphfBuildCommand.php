<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\MaintenanceLockService;
use App\Service\Mphf\MphfBuildService;
use App\Style\EmberNexusStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

use function Safe\pcntl_signal;

#[AsCommand(name: 'mphf:build', description: 'Builds and publishes a new element type index (id to node label / relationship type).')]
class MphfBuildCommand extends Command
{
    public function __construct(
        private MphfBuildService $mphfBuildService,
        private MaintenanceLockService $maintenanceLockService,
    ) {
        parent::__construct();
    }

    /**
     * @SuppressWarnings("PHPMD.ExitExpression")
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new EmberNexusStyle($input, $output);
        $io->title('MPHF build');
        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            $release = function (): never {
                $this->maintenanceLockService->releaseAllHeld();
                exit(1);
            };
            pcntl_signal(2, $release);
            pcntl_signal(15, $release);
        }

        try {
            $result = $this->mphfBuildService->build(static fn (string $line) => $io->writeln('  '.$line));
        } catch (Throwable $throwable) {
            $io->error(sprintf('Build failed: %s', $throwable->getMessage()));

            return Command::FAILURE;
        }
        if ($result->isSkipped()) {
            $io->warning(sprintf('Build skipped: %s.', (string) $result->getMessage()));

            return Command::SUCCESS;
        }
        $io->writeln(sprintf(
            '  Version <info>%s</info>: %d ids, %s bytes, %.1f s, %d old file(s) deleted.',
            (string) $result->getVersion(),
            $result->getKeyCount(),
            number_format($result->getSizeInBytes()),
            $result->getDurationInSeconds(),
            $result->getDeletedFileCount(),
        ));
        $io->finalMessage('Done.');

        return Command::SUCCESS;
    }
}
