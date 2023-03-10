<?php

namespace App\Command;

use App\Style\EonStyle;
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
#[AsCommand(name: 'backup:list')]
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
        $this->io = new EonStyle($input, $output);

        $rows = [];
        $header = [
            'Name',
            'Hostname',
            'Version',
            'Date',
            'Node Count',
            'Relation Count',
            'File Count',
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
            $rows[] = [
                $backupFolder->path(),
                $data['hostname'] ?? '-',
                $data['version'] ?? '-',
                $data['backupCreated'] ?? '-',
                $data['nodeCount'] ?? '-',
                $data['relationCount'] ?? '-',
                $data['fileCount'] ?? '-',
            ];
        }

        $table = $this->io->createTable();
        $table->setHeaders($header);
        $table->setRows($rows);
        $table->setStyle('compact');
        $table->render();

        $this->io->newLine();

        return Command::SUCCESS;
    }
}
