<?php

namespace App\Command;

use App\Style\EmberNexusStyle;
use Exception;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\MountManager;
use League\Flysystem\Visibility;
use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\filesize;
use function Safe\sha1_file;
use function Safe\unlink;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'backup:fetch', description: 'Downloads backup from remote source to local storage.')]
class BackupFetchCommand extends Command
{
    private EmberNexusStyle $io;

    public function __construct(
        private FilesystemOperator $backupStorage
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of the backup');
        $this->addArgument('source', InputArgument::REQUIRED, 'URL to the remote backup, HTTP(S) protocol, must be ZIP archive');
        $this->addOption('force', 'f', InputOption::VALUE_NEGATABLE, 'If active, will overwrite existing backups with the same name', false);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Backup Fetch');

        $this->checkBackupNameIsAvailable($input->getArgument('name'), $input->getOption('force'));

        $this->io->startSection('Downloading and inspecting archive');
        $tempFilePath = sprintf(
            '%s/%s.zip',
            sys_get_temp_dir(),
            uniqid()
        );
        file_put_contents($tempFilePath, file_get_contents($input->getArgument('source')));
        $fileSize = filesize($tempFilePath);
        if (!$fileSize) {
            $fileSize = 0;
        }
        $this->io->writeln(sprintf(
            'Loaded archive is <info>%s</info> big.',
            $this->formatBytes($fileSize)
        ));
        $this->io->writeln(sprintf(
            'SHA1 sum of file is <info>%s</info>.',
            sha1_file($tempFilePath)
        ));
        $provider = new FilesystemZipArchiveProvider($tempFilePath);
        $adapter = new ZipArchiveAdapter($provider);
        $filesystem = new Filesystem($adapter);
        $backupName = $input->getArgument('name');
        $backupLocation = $this->findBackupRootFolder($filesystem);
        if (null === $backupLocation) {
            throw new Exception('Unable to find the file summary.json in backup archive.');
        }
        $this->io->writeln(sprintf('Found backup inside ZIP in folder <info>%s</info>.', $backupLocation));
        if (!$filesystem->directoryExists(sprintf('%s/node', $backupLocation))) {
            throw new Exception('ZIP archive does not contain required node folder.');
        }
        if (!$filesystem->directoryExists(sprintf('%s/relation', $backupLocation))) {
            throw new Exception('ZIP archive does not contain required relation folder.');
        }
        if (!$filesystem->directoryExists(sprintf('%s/file', $backupLocation))) {
            throw new Exception('ZIP archive does not contain required file folder.');
        }
        $this->io->writeln('Required folders exist.');
        $this->io->stopSection('Download complete.');

        $this->extractNodes($filesystem, $backupLocation, $backupName);
        $this->extractRelations($filesystem, $backupLocation, $backupName);
        $this->extractFiles($filesystem, $backupLocation, $backupName);
        $this->extractSummaryJson($filesystem, $backupLocation, $backupName);

        $this->io->startSection('Cleaning up temporary files');
        unset($filesystem, $adapter, $provider);
        unlink($tempFilePath);
        $this->io->stopSection('Cleanup complete.');

        $this->io->success('Fetched backup successfully.');

        return Command::SUCCESS;
    }

    /**
     * @see https://stackoverflow.com/a/22514334/4417327
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $unit = ['B', 'KB', 'MB', 'GB'];
        $exp = (int) floor(log($bytes, 1024)) | 0;

        return round($bytes / pow(1024, $exp), $precision).' '.$unit[$exp];
    }

    public function extractNodes(Filesystem $filesystem, string $backupLocation, string $backupName): void
    {
        $this->io->startSection('Extracting nodes');
        $this->copyFolder(
            $filesystem,
            $backupLocation.'/node',
            $this->backupStorage,
            sprintf('%s/node', $backupName)
        );
        $this->io->stopSection('All nodes extracted.');
    }

    public function extractRelations(Filesystem $filesystem, string $backupLocation, string $backupName): void
    {
        $this->io->startSection('Extracting relations');
        $this->copyFolder(
            $filesystem,
            $backupLocation.'/relation',
            $this->backupStorage,
            sprintf('%s/relation', $backupName)
        );
        $this->io->stopSection('All relations extracted.');
    }

    public function extractFiles(Filesystem $filesystem, string $backupLocation, string $backupName): void
    {
        $this->io->startSection('Extracting files');
        $this->copyFolder(
            $filesystem,
            $backupLocation.'/file',
            $this->backupStorage,
            sprintf('%s/file', $backupName)
        );
        $this->io->stopSection('All files extracted.');
    }

    public function extractSummaryJson(Filesystem $filesystem, string $backupLocation, string $backupName): void
    {
        $this->io->startSection('Extracting summary.json');
        $this->copyFile(
            $filesystem,
            $backupLocation.'/summary.json',
            $this->backupStorage,
            sprintf('%s/summary.json', $backupName)
        );
        $this->io->stopSection('summary.json extracted.');
    }

    public function copyFolder(FilesystemOperator $source, string $sourcePath, FilesystemOperator $destination, string $destinationPath): void
    {
        $manager = new MountManager([
            'source' => $source,
            'dest' => $destination,
        ]);
        $manager->createDirectory(sprintf('dest://%s', $destinationPath));
        $listing = $manager->listContents('source://'.$sourcePath, true);
        $progressBar = new ProgressBar($this->io);
        $progressBar->start();
        /** @var \League\Flysystem\StorageAttributes $item */
        foreach ($listing as $item) {
            $itemPath = $item->path();
            $itemName = basename($itemPath);
            $itemDir = str_replace(sprintf('source://%s', $sourcePath), '', dirname($itemPath));

            if ($item->isFile()) {
                $manager->copy(
                    $itemPath,
                    sprintf('dest://%s%s/%s', $destinationPath, $itemDir, $itemName),
                    [
                        'visibility' => Visibility::PUBLIC,
                    ]
                );
            }
            $progressBar->advance();

            if ($item->isDir()) {
                $manager->createDirectory(sprintf('dest://%s/%s/%s', $destinationPath, $itemDir, $itemName));
            }
        }
        $progressBar->finish();
        $progressBar->clear();
    }

    public function copyFile(FilesystemOperator $source, string $sourcePath, FilesystemOperator $destination, string $destinationPath): void
    {
        $manager = new MountManager([
            'source' => $source,
            'dest' => $destination,
        ]);
        $manager->createDirectory(sprintf('dest://%s', dirname($destinationPath)));
        $manager->copy(
            sprintf('source://%s', $sourcePath),
            sprintf('dest://%s', $destinationPath),
            [
                'visibility' => Visibility::PUBLIC,
            ]
        );
    }

    private function findBackupRootFolder(Filesystem $filesystem): ?string
    {
        $stack = ['/'];
        while (count($stack) > 0) {
            $currentPath = array_shift($stack);
            $listing = $filesystem->listContents($currentPath, false);
            /** @var \League\Flysystem\StorageAttributes $item */
            foreach ($listing as $item) {
                if ($item->isFile() && str_ends_with($item->path(), 'summary.json')) {
                    return $currentPath;
                }
                if ($item->isDir()) {
                    array_push($stack, $item->path());
                }
            }
        }

        return null;
    }

    private function checkBackupNameIsAvailable(string $name, bool $force): void
    {
        if ($this->backupStorage->directoryExists($name)) {
            if (!$force) {
                throw new Exception(sprintf("Unable to fetch remote backup with name '%s', as another backup with the same name already exists. Delete it or use --force to overwrite existing backup.", $name));
            }
            $this->backupStorage->deleteDirectory($name);
        }
        $this->backupStorage->createDirectory($name);
    }
}
