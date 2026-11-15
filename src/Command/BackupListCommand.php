<?php

declare(strict_types=1);

namespace App\Command;

use App\Style\EmberNexusStyle;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\DependencyInjection\Attribute\Target;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'backup:list', description: 'Lists all available local backups.')]
class BackupListCommand extends Command
{
    private OutputStyle $io;

    public function __construct(
        #[Target('backup.storage')]
        private FilesystemOperator $backupStorage,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Backup List');

        $this->logger->notice('Notice :D 789');

        /** @var list<array<int, string>> $rows */
        $rows = [];
        $header = [
            'Name',
            'Hostname',
            'Version',
            'Date',
            'Nodes',
            'Edges',
            'Files',
        ];

        $backupFolders = $this->backupStorage->listContents('/');
        foreach ($backupFolders as $backupFolder) {
            if (!$this->backupStorage->fileExists($backupFolder->path().'/summary.json')) {
                $this->logger->info(sprintf(
                    'Found folder within backup folder which does not contain a summary.json file: %s',
                    $backupFolder->path()
                ));
                continue;
            }
            /** @var array<array-key, mixed>|string|int|float|bool|null $data */
            $data = \Safe\json_decode($this->backupStorage->read($backupFolder->path().'/summary.json'), true);
            if (!is_array($data)) {
                $data = [];
            }
            $backupCreated = $this->getSummaryValue($data, 'backupCreated');
            if ($this->io->getLineLength() <= 100) {
                $backupCreated = explode(' ', $backupCreated)[0];
            }
            $rows[] = [
                $backupFolder->path(),
                $this->getSummaryValue($data, 'hostname'),
                $this->getSummaryValue($data, 'version'),
                $backupCreated,
                $this->getSummaryValue($data, 'nodeCount'),
                $this->getSummaryValue($data, 'relationCount'),
                $this->getSummaryValue($data, 'fileCount'),
            ];
        }

        // sort backups by creation date, desc
        usort($rows, function (array $a, array $b): int {
            return strcmp($b[3] ?? '', $a[3] ?? '');
        });

        $table = $this->io->createCompactTable();
        $table->setHeaders($header);
        $table->setRows($rows);
        $table->render();
        $this->io->newLine();

        $this->io->finalMessage('Command finished successfully.');

        return Command::SUCCESS;
    }

    /**
     * @param array<array-key, mixed> $summary
     */
    private function getSummaryValue(array $summary, string $key): string
    {
        if (!isset($summary[$key]) || !is_scalar($summary[$key])) {
            return '-';
        }

        return (string) $summary[$key];
    }
}
