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

use function Safe\preg_match;

/**
 * Converts a backup with legacy UUIDv4 element ids into a backup with UUIDsv2a7 element ids.
 *
 * The source backup is never modified: all data is copied into a new backup folder, which must not exist yet.
 *
 * @psalm-suppress PropertyNotSetInConstructor $io
 *
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
#[AsCommand(name: 'backup:convert', description: 'Converts a backup with legacy UUIDv4 element ids into a new backup with UUIDsv2a7 element ids.')]
class BackupConvertCommand extends Command
{
    use CommandInputTrait;

    public const string DEFAULT_MAPPING_FILE = 'uuid-mapping.csv';
    /** First API version which creates backups in the new format (UUIDsv2a7 ids, createdBy instead of CREATED). */
    public const string FIRST_NEW_FORMAT_VERSION = '0.2.0';

    private EmberNexusStyle $io;
    private LegacyUuidMap $legacyUuidMap;
    private string $sourceName;
    private string $targetName;
    private int $nodeCount = 0;
    private int $relationCount = 0;
    private int $fileCount = 0;
    private int $otherCount = 0;
    private int $errorCount = 0;
    private int $createdRelationCount = 0;
    /** @var array<string, string> lower case old id of the created element => id of its creator (already mapped) */
    private array $createdBy = [];
    /** @var array<string, true> paths of the CREATED relation files, which are replaced by the createdBy property */
    private array $createdRelationPaths = [];
    /** @var array<string, true> */
    private array $nodeTypes = [];
    /** @var array<string, true> */
    private array $relationTypes = [];

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
        $this->addOption('force', 'f', InputOption::VALUE_NONE, sprintf('Also convert backups which were created with version %s or newer', self::FIRST_NEW_FORMAT_VERSION));
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

        $this->checkSourceVersion($this->isOptionActive($input, 'force'));

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

        $this->collectCreatedRelations($paths);

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
            if (!$this->isElementJson($path, 'relation')) {
                continue;
            }
            if (array_key_exists($path, $this->createdRelationPaths)) {
                // replaced by the createdBy property of the created element
                ++$this->createdRelationCount;
                continue;
            }
            $this->convertElementJson($path);
            ++$this->relationCount;
        }
        $this->io->stopSection(sprintf(
            'Converted <info>%d</info> relations, replaced <info>%d</info> CREATED relations by createdBy properties.',
            $this->relationCount,
            $this->createdRelationCount
        ));

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
            if ('summary.json' === $path) {
                $this->convertSummary($path);
            } else {
                $this->copy($path, $path);
            }
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

    /**
     * Only backups of older versions are converted; the version is stored in the `version` property of the backup's
     * summary.json by backup:create. Development builds (`dev`) and backups without a version can not be classified,
     * they are converted with a warning.
     */
    private function checkSourceVersion(bool $force): void
    {
        $summary = $this->backupStorage->fileExists($this->sourceName.'/summary.json')
            ? $this->readElementArray('summary.json')
            : null;
        $version = $summary['version'] ?? null;
        if (!is_string($version) || '' === trim($version)) {
            $this->io->warning("The backup has no 'version' in its summary.json, it is assumed to be created before ".self::FIRST_NEW_FORMAT_VERSION.'.');

            return;
        }
        if (1 !== preg_match('/^v?(\d+)\.(\d+)\.(\d+)/', trim($version), $matches) || !isset($matches[1], $matches[2], $matches[3])) {
            $this->io->warning(sprintf("The backup was created with the non-release version '%s', it is assumed to be created before %s.", $version, self::FIRST_NEW_FORMAT_VERSION));

            return;
        }
        // pre-releases of the new version already write the new format, so only the numeric part is compared
        if (version_compare($matches[1].'.'.$matches[2].'.'.$matches[3], self::FIRST_NEW_FORMAT_VERSION, '>=')) {
            if (!$force) {
                throw new LogicException(sprintf("Backup '%s' was created with version %s, but only backups created before %s have to be converted. Use --force to convert it anyway.", $this->sourceName, $version, self::FIRST_NEW_FORMAT_VERSION));
            }
            $this->io->warning(sprintf('The backup was created with version %s, which already uses the new format. Converting it anyway.', $version));
        }
    }

    /**
     * Older backups contain a `CREATED` relation from the creating user to each created element; it is replaced by the
     * property `createdBy` (id of the user) of the created element. The node ids are mapped first, so they keep the
     * timestamp of their creation, no matter in which order the files are read.
     *
     * @param list<string> $paths
     */
    private function collectCreatedRelations(array $paths): void
    {
        foreach ($paths as $path) {
            if ($this->isElementJson($path, 'node')) {
                $array = $this->readElementArray($path);
                if (null !== $array) {
                    $this->legacyUuidMap->mapRawElement($array);
                }
            }
        }
        foreach ($paths as $path) {
            if (!$this->isElementJson($path, 'relation')) {
                continue;
            }
            $array = $this->readElementArray($path);
            if (null === $array || 'CREATED' !== ($array['type'] ?? null)) {
                continue;
            }
            $start = $array['start'] ?? null;
            $end = $array['end'] ?? null;
            if (!is_string($start) || !is_string($end)) {
                continue;
            }
            $this->createdRelationPaths[$path] = true;
            $key = strtolower(trim($end));
            if (array_key_exists($key, $this->createdBy)) {
                $this->io->warning(sprintf("Element '%s' has more than one CREATED relation, '%s' is ignored.", $end, $path));
                continue;
            }
            $this->createdBy[$key] = $this->legacyUuidMap->mapId($start);
        }
    }

    /**
     * @return array<string, mixed>|null null if the file is not a valid JSON object, it is reported when it is converted
     */
    private function readElementArray(string $path): ?array
    {
        try {
            /** @psalm-suppress MixedAssignment */
            $array = \Safe\json_decode($this->backupStorage->read($this->sourceName.'/'.$path), true);
        } catch (Throwable) {
            return null;
        }
        /** @var array<string, mixed>|null $result */
        $result = is_array($array) ? $array : null;

        return $result;
    }

    /**
     * Keeps the summary consistent with the converted elements: counts, graph structure and version. The original version
     * is kept as `convertedFromVersion`.
     */
    private function convertSummary(string $path): void
    {
        $raw = $this->backupStorage->read($this->sourceName.'/'.$path);
        try {
            $summary = \Safe\json_decode($raw, true);
            if (!is_array($summary)) {
                throw new LogicException('Expected JSON object.');
            }
        } catch (Throwable) {
            $this->copy($path, $path);

            return;
        }
        $nodeTypes = array_keys($this->nodeTypes);
        $relationTypes = array_keys($this->relationTypes);
        sort($nodeTypes, SORT_STRING);
        sort($relationTypes, SORT_STRING);
        $graphStructure = ['nodeTypes' => $nodeTypes, 'relationTypes' => $relationTypes];
        $converted = [];
        /** @psalm-suppress MixedAssignment */
        foreach ($summary as $key => $value) {
            $converted[$key] = match ($key) {
                'nodeCount' => $this->nodeCount,
                'relationCount' => $this->relationCount,
                'graphStructure' => $graphStructure,
                // the converted backup uses the new format, backup:load refuses backups of older versions
                'version' => self::FIRST_NEW_FORMAT_VERSION,
                default => $value,
            };
            if ('version' === $key && is_string($value) && '' !== trim($value)) {
                $converted['convertedFromVersion'] = $value;
            }
            if ('fileCount' === $key && !array_key_exists('graphStructure', $summary)) {
                $converted['graphStructure'] = $graphStructure;
            }
        }
        if (!array_key_exists('version', $converted)) {
            $converted['version'] = self::FIRST_NEW_FORMAT_VERSION;
        }
        $json = $this->encodeJson($converted);
        if (str_ends_with($raw, "\n")) {
            $json .= "\n";
        }
        $this->backupStorage->write($this->targetName.'/'.$path, $json);
    }

    private function encodeJson(mixed $value): string
    {
        $json = \Safe\json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        // keep the 2 space indentation used by the backup files
        $lines = [];
        foreach (explode("\n", $json) as $line) {
            $content = ltrim($line, ' ');
            $lines[] = str_repeat(' ', intdiv(strlen($line) - strlen($content), 2)).$content;
        }

        return implode("\n", $lines);
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
            $this->collectType($path, $array);
            /** @psalm-suppress MixedAssignment */
            $rawId = $array['id'] ?? null;
            $createdBy = $this->createdBy[strtolower(trim(is_string($rawId) ? $rawId : ''))] ?? null;
            if (null !== $createdBy) {
                $data = $this->getOrCreateData($object);
                if (!isset($data->createdBy)) {
                    $data->createdBy = $createdBy;
                    $changed = true;
                }
            }
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
        $json = $this->encodeJson($object);
        if (str_ends_with($raw, "\n")) {
            $json .= "\n";
        }
        $this->backupStorage->write($this->targetName.'/'.$this->targetPathForElement($path, $newId), $json);
    }

    /**
     * @psalm-suppress MixedAssignment
     */
    private function getOrCreateData(stdClass $object): stdClass
    {
        $data = $object->data ?? null;
        if ($data instanceof stdClass) {
            return $data;
        }
        $data = new stdClass();
        $object->data = $data;

        return $data;
    }

    /**
     * @param array<string, mixed> $array
     */
    private function collectType(string $path, array $array): void
    {
        $type = $array['type'] ?? null;
        if (!is_string($type)) {
            return;
        }
        if (str_starts_with($path, 'node/')) {
            $this->nodeTypes[$type] = true;
        } else {
            $this->relationTypes[$type] = true;
        }
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
