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

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'backup:list', description: 'Lists all available local backups.')]
class BackupListCommand extends Command
{
    private OutputStyle $io;

    public function __construct(
        private FilesystemOperator $backupStorage,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Backup List');

        $this->logger->notice('Notice :D 789');

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
            $data = \Safe\json_decode($this->backupStorage->read($backupFolder->path().'/summary.json'), true);
            $backupCreated = $data['backupCreated'] ?? '-';
            if ($this->io->getLineLength() <= 100) {
                $backupCreated = explode(' ', $backupCreated)[0];
            }
            $rows[] = [
                $backupFolder->path(),
                $data['hostname'] ?? '-',
                $data['version'] ?? '-',
                $backupCreated,
                $data['nodeCount'] ?? '-',
                $data['relationCount'] ?? '-',
                $data['fileCount'] ?? '-',
            ];
        }

        $table = $this->io->createCompactTable();
        $table->setHeaders($header);
        $table->setRows($rows);
        $table->render();
        $this->io->newLine();

        $this->io->finalMessage('Command finished successfully.');

        return Command::SUCCESS;
    }
}
