<?php

declare(strict_types=1);

namespace App\Command;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\StorageService;
use App\Style\EmberNexusStyle;
use App\Trait\CommandInputTrait;
use App\Type\LegacyUuidMap;
use App\Type\Uuid;
use League\Flysystem\FilesystemOperator;
use LogicException;
use Psr\Log\LoggerInterface;
use stdClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Throwable;

/**
 * Converts a backup with legacy UUIDv4 element ids into a backup with UUIDsv2a7 element ids.
 *
 * The source backup is never modified: all data is copied into a new backup folder, which must not exist yet.
 *
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'backup:convert', description: 'Converts a backup with legacy UUIDv4 element ids into a new backup with UUIDsv2a7 element ids.')]
class BackupConvertCommand extends Command
{
    use CommandInputTrait;

    public const string DEFAULT_MAPPING_FILE = 'uuid-mapping.csv';

    private EmberNexusStyle $io;
    private LegacyUuidMap $legacyUuidMap;
    private string $sourceName;
    private string $targetName;
    private int $nodeCount = 0;
    private int $relationCount = 0;
    private int $fileCount = 0;
    private int $otherCount = 0;
    private int $errorCount = 0;

    public function __construct(
        #[Target('backup.storage')]
        private FilesystemOperator $backupStorage,
        private StorageService $storageService,
        private LoggerInterface $logger,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('source', InputArgument::REQUIRED, 'Name of the existing backup');
        $this->addOption('target', 't', InputOption::VALUE_REQUIRED, "Name of the new backup, defaults to '<source>-converted'");
        $this->addOption('mapping-output', null, InputOption::VALUE_REQUIRED, sprintf("Path of the CSV file which receives the id mapping, defaults to '%s' within the new backup", self::DEFAULT_MAPPING_FILE));
    }

    protected function createInvalidInputException(string $detail): Throwable
    {
        return $this->server500LogicErrorExceptionFactory->createFromTemplate($detail);
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);
        $this->io->title('Backup Convert');

        $this->sourceName = $this->checkName($this->getStringArgument($input, 'source'));
        $this->targetName = $this->checkName($this->getNullableStringOption($input, 'target') ?? $this->sourceName.'-converted');
        $mappingOutput = $this->getNullableStringOption($input, 'mapping-output');

        if (!$this->backupStorage->directoryExists($this->sourceName)) {
            throw new LogicException(sprintf("Backup with the name '%s' does not exist", $this->sourceName));
        }
        if ($this->sourceName === $this->targetName || $this->backupStorage->directoryExists($this->targetName) || $this->backupStorage->fileExists($this->targetName)) {
            throw new LogicException(sprintf("Target backup '%s' already exists; the source backup is never modified or overwritten", $this->targetName));
        }

        // all legacy ids without 'created' property share the time of this conversion
        $this->legacyUuidMap = LegacyUuidMap::createFromNow();

        /** @var list<string> $paths */
        $paths = [];
        foreach ($this->backupStorage->listContents($this->sourceName, true) as $item) {
            if ($item->isFile()) {
                $paths[] = $this->relativePath($item->path());
            }
        }
        sort($paths, SORT_STRING);

        $this->backupStorage->createDirectory($this->targetName);

        // nodes first, so that relations can reference the already converted node ids
        $this->io->startSection('Step 1 of 4: Converting Nodes');
        foreach ($paths as $path) {
            if ($this->isElementJson($path, 'node')) {
                $this->convertElementJson($path);
                ++$this->nodeCount;
            }
        }
        $this->io->stopSection(sprintf('Converted <info>%d</info> nodes.', $this->nodeCount));

        $this->io->startSection('Step 2 of 4: Converting Relations');
        foreach ($paths as $path) {
            if ($this->isElementJson($path, 'relation')) {
                $this->convertElementJson($path);
                ++$this->relationCount;
            }
        }
        $this->io->stopSection(sprintf('Converted <info>%d</info> relations.', $this->relationCount));

        $this->io->startSection('Step 3 of 4: Copying Files');
        foreach ($paths as $path) {
            if (str_starts_with($path, 'file/') && !str_ends_with($path, '/.gitignore')) {
                $this->convertFile($path);
                ++$this->fileCount;
            }
        }
        $this->io->stopSection(sprintf('Copied <info>%d</info> files.', $this->fileCount));

        $this->io->startSection('Step 4 of 4: Copying Remaining Files');
        foreach ($paths as $path) {
            if ($this->isElementJson($path, 'node') || $this->isElementJson($path, 'relation')) {
                continue;
            }
            if (str_starts_with($path, 'file/') && !str_ends_with($path, '/.gitignore')) {
                continue;
            }
            $this->copy($path, $path);
            ++$this->otherCount;
        }
        $this->writeMapping($mappingOutput);
        $this->io->stopSection(sprintf('Copied <info>%d</info> other files.', $this->otherCount));

        if ($this->errorCount > 0) {
            $this->io->warning(sprintf('%d elements could not be parsed and were copied unchanged.', $this->errorCount));
        }
        $this->io->finalMessage(sprintf(
            "Converted %d legacy element ids. New backup '%s' was created, source backup '%s' is unchanged.",
            $this->legacyUuidMap->count(),
            $this->targetName,
            $this->sourceName
        ));

        return Command::SUCCESS;
    }

    private function writeMapping(?string $mappingOutput): void
    {
        $csv = $this->legacyUuidMap->toCsv();
        if (null === $mappingOutput) {
            $this->backupStorage->write($this->targetName.'/'.self::DEFAULT_MAPPING_FILE, $csv);

            return;
        }
        \Safe\file_put_contents($mappingOutput, $csv);
    }

    private function convertElementJson(string $path): void
    {
        $raw = $this->backupStorage->read($this->sourceName.'/'.$path);
        try {
            $object = \Safe\json_decode($raw, false);
            $array = \Safe\json_decode($raw, true);
            if (!$object instanceof stdClass || !is_array($array)) {
                throw new LogicException('Expected JSON object.');
            }
            /** @var array<string, mixed> $array */
            $mapped = $this->legacyUuidMap->mapRawElement($array);
            $changed = false;
            foreach (['id', 'start', 'end'] as $key) {
                if (isset($mapped[$key]) && ($object->{$key} ?? null) !== $mapped[$key]) {
                    $object->{$key} = $mapped[$key];
                    $changed = true;
                }
            }
        } catch (Throwable $e) {
            ++$this->errorCount;
            $this->logger->error(sprintf('Unable to convert %s, copying it unchanged: %s', $path, $e->getMessage()));
            $this->io->error(sprintf('Unable to convert %s, copying it unchanged: %s', $path, $e->getMessage()));
            $this->copy($path, $path);

            return;
        }
        $newId = is_string($mapped['id'] ?? null) ? (string) $mapped['id'] : '';
        if (!$changed) {
            $this->copy($path, $path);

            return;
        }
        $json = \Safe\json_encode($object, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        // keep the 2 space indentation used by the backup files
        $lines = [];
        foreach (explode("\n", $json) as $line) {
            $content = ltrim($line, ' ');
            $lines[] = str_repeat(' ', intdiv(strlen($line) - strlen($content), 2)).$content;
        }
        $json = implode("\n", $lines);
        if (str_ends_with($raw, "\n")) {
            $json .= "\n";
        }
        $this->backupStorage->write($this->targetName.'/'.$this->targetPathForElement($path, $newId), $json);
    }

    private function convertFile(string $path): void
    {
        [$directory, $name] = $this->splitPath($path);
        $parts = explode('.', $name, 2);
        $id = $parts[0];
        if (!Uuid::isLegacyUuidV4($id) && !Uuid::isValid($id)) {
            $this->copy($path, $path);

            return;
        }
        $newId = $this->legacyUuidMap->mapId($id);
        $extension = $parts[1] ?? '';
        $newName = $this->storageService->appendExtension($newId, $extension);
        $newDirectory = $this->targetDirectory($directory, 'file', $id, $newId);
        $this->copy($path, ('' === $newDirectory ? '' : $newDirectory.'/').$newName);
    }

    /**
     * Path of a converted node or relation file; the file is renamed if its name is the element id.
     */
    private function targetPathForElement(string $path, string $newId): string
    {
        [$directory, $name] = $this->splitPath($path);
        $baseName = substr($name, 0, -strlen('.json'));
        if (!Uuid::isLegacyUuidV4($baseName) && !Uuid::isValid($baseName)) {
            return $path;
        }
        $root = explode('/', $path, 2)[0];
        $newDirectory = $this->targetDirectory($directory, $root, $baseName, $newId);

        return ('' === $newDirectory ? '' : $newDirectory.'/').$newId.'.json';
    }

    /**
     * Backups created by backup:create store elements in nested folders, which are derived from the element id; these
     * folders are recalculated with the same number of levels. All other folder structures are kept as they are.
     */
    private function targetDirectory(string $directory, string $root, string $oldId, string $newId): string
    {
        $folders = explode('/', $directory);
        array_shift($folders);
        $oldHex = strtolower(str_replace('-', '', $oldId));
        $isNested = count($folders) > 0;
        foreach ($folders as $index => $folder) {
            if (2 !== strlen($folder) || substr($oldHex, 2 * $index, 2) !== $folder) {
                $isNested = false;
            }
        }
        if (!$isNested) {
            return $directory;
        }
        $nested = $this->storageService->uuidToNestedFolderStructure(Uuid::fromString($newId), count($folders));

        return $root.'/'.dirname($nested);
    }

    private function copy(string $from, string $targetPath): void
    {
        $target = $this->targetName.'/'.$targetPath;
        if ($this->backupStorage->fileExists($target)) {
            throw new LogicException(sprintf("Refusing to overwrite '%s' of the new backup; two source files map to the same target.", $target));
        }
        $stream = $this->backupStorage->readStream($this->sourceName.'/'.$from);
        try {
            $this->backupStorage->writeStream($target, $stream);
        } finally {
            \Safe\fclose($stream);
        }
    }

    /**
     * @return array{string, string} directory and file name
     */
    private function splitPath(string $path): array
    {
        $directory = dirname($path);

        return ['.' === $directory ? '' : $directory, basename($path)];
    }

    private function isElementJson(string $path, string $root): bool
    {
        return str_starts_with($path, $root.'/') && str_ends_with($path, '.json');
    }

    private function relativePath(string $path): string
    {
        return substr($path, strlen($this->sourceName) + 1);
    }

    private function checkName(string $name): string
    {
        $name = trim($name);
        if (in_array($name, ['', '.', '..'], true) || str_contains($name, '/') || str_contains($name, '\\')) {
            throw new LogicException(sprintf("Invalid backup name '%s'", $name));
        }

        return $name;
    }
}
