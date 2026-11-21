<?php

declare(strict_types=1);

namespace App\Command;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\DependencyInjection\DeactivatableTraceableEventDispatcher;
use App\EventSystem\EntityManager\Event\ElementUpdateAfterBackupLoadEvent;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\S3OperationFactory;
use App\Helper\Regex;
use App\Service\AppStateService;
use App\Service\ElementManager;
use App\Service\FileHashService;
use App\Service\FileSizeLimitService;
use App\Service\RawToElementService;
use App\Service\S3Service;
use App\Style\EmberNexusStyle;
use App\Trait\CommandInputTrait;
use App\Type\AppStateType;
use App\Type\LegacyUuidMap;
use App\Type\RelationElement;
use App\Type\Uuid;
use Laudis\Neo4j\Databags\Statement;
use League\Flysystem\FilesystemOperator;
use LogicException;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Throwable;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'backup:load', description: 'Loads a local backup into the empty database.')]
class BackupLoadCommand extends Command
{
    use CommandInputTrait;

    private string $backupName;
    private int $relationCount = 0;
    private int $fileCount = 0;
    private int $nodeCount = 0;
    private int $pageSize = 250;
    private bool $skipVerify = false;
    private ?string $legacyMappingOutput = null;
    private LegacyUuidMap $legacyUuidMap;
    private int $futureTimestampCount = 0;

    private EmberNexusStyle $io;

    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private Client $redisClient,
        #[Target('backup.storage')]
        private FilesystemOperator $backupStorage,
        private RawToElementService $rawToElementService,
        private EventDispatcherInterface $eventDispatcher,
        private AppStateService $appStateService,
        private ElasticEntityManager $elasticEntityManager,
        private S3Service $s3Service,
        private S3OperationFactory $s3OperationFactory,
        private FileSizeLimitService $fileSizeLimitService,
        private FileHashService $fileHashService,
        private LoggerInterface $logger,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of the backup');
        $this->addOption('legacy-mapping-output', null, InputOption::VALUE_REQUIRED, 'If the backup contains legacy UUIDv4 element ids, the mapping to the new ids is written to this CSV file');
        $this->addOption('skip-verify', null, InputOption::VALUE_NONE, "Skip verifying file contents against their stored 'file.hash' values");
    }

    protected function createInvalidInputException(string $detail): Throwable
    {
        return $this->server500LogicErrorExceptionFactory->createFromTemplate($detail);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->appStateService->setAppState(AppStateType::LOADING_BACKUP);
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Backup Load');

        $this->checkDatabaseIsEmpty();

        if ($this->eventDispatcher instanceof DeactivatableTraceableEventDispatcher) {
            $this->eventDispatcher->deactivate();
        }

        $this->backupName = $this->checkBackupName($this->getStringArgument($input, 'name'));
        $this->skipVerify = $this->isOptionActive($input, 'skip-verify');
        $this->legacyMappingOutput = $this->getNullableStringOption($input, 'legacy-mapping-output');
        // legacy ids without created timestamp share the import time of this backup load
        $this->legacyUuidMap = LegacyUuidMap::createFromNow();

        $this->loadSummary();

        $this->loadNodes();
        $this->loadRelations();
        $this->loadFiles();
        $this->afterBackupTasks();
        $this->reportUuidConversion();

        $this->io->finalMessage('Backup successfully loaded.');

        return Command::SUCCESS;
    }

    private function loadNodes(): void
    {
        $this->io->startSection('Step 1 of 4: Loading Nodes');
        $progressBar = $this->io->createProgressBarInInteractiveTerminal($this->nodeCount);
        $progressBar?->display();
        $nodeFiles = $this->backupStorage->listContents($this->backupName.'/node/', true);
        $pageCount = 0;
        $totalCount = 0;
        foreach ($nodeFiles as $nodeFile) {
            if (!$nodeFile->isFile()) {
                continue;
            }
            if (!str_ends_with($nodeFile->path(), '.json')) {
                continue;
            }
            $data = $this->legacyUuidMap->mapRawElement($this->readJsonObject($nodeFile->path()));
            $nodeElement = $this->rawToElementService->rawToElement($data, true);
            unset($data);
            $this->warnIfIdIsInFuture($nodeElement->getId());
            $this->elementManager->create($nodeElement);
            ++$pageCount;
            if ($pageCount >= $this->pageSize) {
                $this->elementManager->flush();
                $progressBar?->advance($pageCount);
                $totalCount += $pageCount;
                $pageCount = 0;
            }
        }
        $this->elementManager->flush();
        $progressBar?->advance($pageCount);
        $progressBar?->clear();
        $totalCount += $pageCount;
        $this->io->stopSection(sprintf(
            'Loaded <info>%d</info> nodes.',
            $totalCount
        ));
    }

    private function loadRelations(): void
    {
        $this->io->startSection('Step 2 of 4: Loading Relations');
        $progressBar = $this->io->createProgressBarInInteractiveTerminal($this->relationCount);
        $progressBar?->display();
        $relationFiles = $this->backupStorage->listContents($this->backupName.'/relation/', true);
        $totalCount = 0;
        $pageCount = 0;
        $skippedCount = 0;
        foreach ($relationFiles as $relationFile) {
            if (!$relationFile->isFile()) {
                continue;
            }
            if (!str_ends_with($relationFile->path(), '.json')) {
                continue;
            }
            try {
                $data = $this->legacyUuidMap->mapRawElement($this->readJsonObject($relationFile->path()));
                $relationElement = $this->rawToElementService->rawToElement($data, true);
                unset($data);
                $this->warnIfIdIsInFuture($relationElement->getId());
                $this->elementManager->create($relationElement);
            } catch (Throwable $e) {
                // one broken relation file, e.g. invalid JSON or an invalid id, must not abort the whole restore
                ++$skippedCount;
                $this->reportFileError(sprintf(
                    'Unable to load relation from %s; skipping relation: %s: %s',
                    $relationFile->path(),
                    $e::class,
                    $e->getMessage()
                ));
                continue;
            }
            ++$pageCount;
            if ($pageCount >= $this->pageSize) {
                $this->elementManager->flush();
                $progressBar?->advance($pageCount);
                $totalCount += $pageCount;
                $pageCount = 0;
            }
        }
        $this->elementManager->flush();
        $progressBar?->advance($pageCount);
        $progressBar?->clear();
        $totalCount += $pageCount;
        $this->io->stopSection(sprintf(
            'Loaded <info>%d</info> relations%s.',
            $totalCount,
            0 === $skippedCount ? '' : sprintf(', skipped <error>%d</error> broken relation files', $skippedCount)
        ));
    }

    private function parseFilenameAsUuidFromPath(string $path, bool $extensionIsOptional = false): false|Uuid
    {
        $filename = basename($path);
        $parts = explode('.', $filename, 2);
        if (2 !== count($parts) && !$extensionIsOptional) {
            return false;
        }
        $name = $parts[0];
        if (!\Safe\preg_match(Regex::UUID, $name) && !Uuid::isLegacyUuidV4($name)) {
            return false;
        }

        return Uuid::fromString($this->legacyUuidMap->mapId($name));
    }

    /**
     * Backups may contain ids with timestamps in the future, e.g. if they were created on a machine with a wrong
     * clock; they are imported anyway.
     */
    private function warnIfIdIsInFuture(?Uuid $id): void
    {
        if (null === $id) {
            return;
        }
        if ($id->getTimestampMs() <= Uuid::nowMs() + RawToElementService::FUTURE_TOLERANCE_MS) {
            return;
        }
        ++$this->futureTimestampCount;
        $this->logger->warning(sprintf('Element id %s of backup contains a timestamp in the future.', $id->toString()));
    }

    private function reportUuidConversion(): void
    {
        if ($this->futureTimestampCount > 0) {
            $this->io->warning(sprintf('%d element ids of the backup contain a timestamp in the future; they were imported as they are.', $this->futureTimestampCount));
        }
        if (0 === $this->legacyUuidMap->count()) {
            return;
        }
        $this->io->warning(sprintf(
            'The backup contains %d legacy UUIDv4 element ids, which were converted to UUIDsv2a7. Existing references to the old ids, e.g. in clients, are no longer valid.',
            $this->legacyUuidMap->count()
        ));
        if (null !== $this->legacyMappingOutput) {
            \Safe\file_put_contents($this->legacyMappingOutput, $this->legacyUuidMap->toCsv());
            $this->io->writeln(sprintf('Wrote id mapping to <info>%s</info>.', $this->legacyMappingOutput));
        }
    }

    /**
     * A skipped file leaves its element with hasFile=true but without S3 object, so it is reported as error on the
     * console and in the logs.
     */
    private function reportFileError(string $message): void
    {
        $this->logger->error($message);
        $this->io->error($message);
    }

    /**
     * Problems with a single file are reported instead of thrown, so that one bad file does not abort the restore.
     */
    private function loadFile(string $path, Uuid $fileId): bool
    {
        $element = $this->elementManager->getElement($fileId);
        if (null === $element) {
            $this->reportFileError(sprintf(
                'Found file in backup without corresponding element; can not import file: %s',
                $path
            ));

            return false;
        }

        try {
            $contentLength = $this->backupStorage->fileSize($path);
            if ($this->fileSizeLimitService->exceedsMaxFileSize($contentLength)) {
                $this->reportFileError(sprintf(
                    "File is %d bytes, which exceeds the configured 'file.maxFileSizeInBytes' of %d; skipping file: %s",
                    $contentLength,
                    $this->fileSizeLimitService->getMaxFileSizeInBytes(),
                    $path
                ));

                return false;
            }

            $hashError = $this->skipVerify ? null : $this->verifyFileHashes($element, $path);
            if (null !== $hashError) {
                $this->reportFileError(sprintf('%s; skipping file: %s', $hashError, $path));

                return false;
            }

            $resource = $this->backupStorage->readStream($path);
            $uploadFileOperation = $this->s3OperationFactory->createUploadFileOperationFromElementAndResource($element, $resource, $contentLength);
            $this->s3Service->uploadFile($uploadFileOperation);
        } catch (Throwable $e) {
            $this->reportFileError(sprintf(
                'Failed to upload file %s: %s',
                $path,
                $e->getMessage()
            ));

            return false;
        }

        return true;
    }

    /**
     * @return string|null error message, or null if all hashes match
     */
    private function verifyFileHashes(NodeElementInterface|RelationElementInterface $element, string $path): ?string
    {
        $expectedHashes = $this->fileHashService->getVerifiableHashesFromFileProperty(
            $element->hasProperty('file') ? $element->getProperty('file') : null
        );
        if ([] === $expectedHashes) {
            return "Element has no verifiable 'file.hash' property";
        }

        $resource = $this->backupStorage->readStream($path);
        try {
            $actualHashes = $this->fileHashService->calculateHashesFromResource($resource, array_keys($expectedHashes));
        } finally {
            \Safe\fclose($resource);
        }

        foreach ($expectedHashes as $algorithm => $expectedHash) {
            if (!hash_equals($expectedHash, $actualHashes[$algorithm])) {
                return sprintf(
                    "File content does not match 'file.hash.%s' (expected %s, got %s)",
                    $algorithm,
                    $expectedHash,
                    $actualHashes[$algorithm]
                );
            }
        }

        return null;
    }

    private function loadFiles(): void
    {
        $this->io->startSection('Step 3 of 4: Loading Files');
        if ($this->skipVerify) {
            $this->io->warning("File hash verification is disabled (--skip-verify); file contents are not checked against 'file.hash'.");
        }
        $progressBar = $this->io->createProgressBarInInteractiveTerminal($this->fileCount);
        $progressBar?->display();
        $files = $this->backupStorage->listContents($this->backupName.'/file/', true);
        $pageCount = 0;
        $totalCount = 0;
        $failedCount = 0;
        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->path();
            // files without extension are stored without a trailing dot
            $fileId = $this->parseFilenameAsUuidFromPath($path, true);
            if (false === $fileId) {
                continue;
            }
            if (!$this->loadFile($path, $fileId)) {
                ++$failedCount;
                continue;
            }

            ++$pageCount;
            if ($pageCount >= $this->pageSize) {
                $progressBar?->advance($pageCount);
                $totalCount += $pageCount;
                $pageCount = 0;
            }
        }
        $this->elementManager->flush();
        $progressBar?->advance($pageCount);
        $progressBar?->clear();
        $totalCount += $pageCount;
        if ($failedCount > 0) {
            $this->io->stopSection(sprintf(
                'Loaded <info>%d</info> files, <comment>%d</comment> could not be loaded and their elements still reference a missing file (see errors above).',
                $totalCount,
                $failedCount
            ));

            return;
        }
        $this->io->stopSection(sprintf(
            'Loaded <info>%d</info> files.',
            $totalCount
        ));
    }

    private function afterBackupTasks(): void
    {
        $this->io->startSection('Step 4 of 4: After Backup Tasks');

        $pageSize = 200;
        $page = 0;
        $endReached = false;
        while (!$endReached) {
            $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
                'MATCH (n) RETURN n.id ORDER BY n.id SKIP $skip LIMIT $limit;',
                [
                    'skip' => $page * $pageSize,
                    'limit' => $pageSize,
                ]
            ));
            if (0 === count($res)) {
                $endReached = true;
            }
            foreach ($res as $row) {
                $rawId = $row->get('n.id');
                if (!is_string($rawId)) {
                    throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property n.id as string, not %s.', get_debug_type($rawId))); // @codeCoverageIgnore
                }
                $id = Uuid::fromString($rawId);
                $element = $this->elementManager->getNode($id);
                if ($element) {
                    $event = new ElementUpdateAfterBackupLoadEvent($element);
                    $this->eventDispatcher->dispatch($event);
                }
            }
            $this->elasticEntityManager->flush();
            ++$page;
        }
        $page = 0;
        $endReached = false;
        while (!$endReached) {
            $res = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
                'MATCH (startNode)-[r]->(endNode) RETURN r.id AS id, type(r) AS type, startNode.id AS startId, endNode.id AS endId ORDER BY r.id SKIP $skip LIMIT $limit;',
                [
                    'skip' => $page * $pageSize,
                    'limit' => $pageSize,
                ]
            ));
            if (0 === count($res)) {
                $endReached = true;
            }
            foreach ($res as $row) {
                $rawId = $row->get('id');
                $rawType = $row->get('type');
                $rawStartId = $row->get('startId');
                $rawEndId = $row->get('endId');
                if (!is_string($rawId) || !is_string($rawType) || !is_string($rawStartId) || !is_string($rawEndId)) {
                    throw $this->server500LogicErrorExceptionFactory->createFromTemplate('Expected cypher response to return the relation properties id, type, startId and endId as string.'); // @codeCoverageIgnore
                }
                // the listeners of this event only need the relation's identity; fetching the full element would look
                // up the relation by its id again, which scans all relations
                $element = (new RelationElement())
                    ->setId(Uuid::fromString($rawId))
                    ->setType($rawType)
                    ->setStart(Uuid::fromString($rawStartId))
                    ->setEnd(Uuid::fromString($rawEndId));
                $this->eventDispatcher->dispatch(new ElementUpdateAfterBackupLoadEvent($element));
            }
            $this->elasticEntityManager->flush();
            ++$page;
        }

        $this->redisClient->flushdb();

        $this->io->stopSection('Finished all after backup tasks.');
    }

    private function checkBackupName(string $backupName): string
    {
        $backupName = trim($backupName);

        if ('' === $backupName) {
            throw new LogicException("Backup name can not be ''");
        }

        if ('.' === $backupName) {
            throw new LogicException("Backup name can not be '.'");
        }

        if ('..' === $backupName) {
            throw new LogicException("Backup name can not be '..'");
        }

        if (!$this->backupStorage->directoryExists($backupName)) {
            throw new LogicException(sprintf("Backup with the name '%s' does not exist", $backupName));
        }

        return $backupName;
    }

    /**
     * @return array<string, mixed>
     */
    private function readJsonObject(string $path): array
    {
        $data = \Safe\json_decode($this->backupStorage->read($path), true);
        if (!is_array($data)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf("Expected file '%s' of backup to contain a JSON object, got %s.", $path, get_debug_type($data)));
        }

        /** @var array<string, mixed> $data */
        return $data;
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function getCountFromSummary(array $summary, string $key): int
    {
        if (!array_key_exists($key, $summary)) {
            return 0;
        }
        $count = $summary[$key];
        if (!is_numeric($count)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf("Expected property '%s' of backup summary to be numeric, not %s.", $key, get_debug_type($count)));
        }

        return (int) $count;
    }

    private function loadSummary(): void
    {
        if (!$this->backupStorage->fileExists($this->backupName.'/summary.json')) {
            throw new LogicException(sprintf("Backup with the name '%s' does not contain a summary.json", $this->backupName));
        }
        $data = $this->readJsonObject($this->backupName.'/summary.json');
        $this->nodeCount = $this->getCountFromSummary($data, 'nodeCount');
        $this->relationCount = $this->getCountFromSummary($data, 'relationCount');
        $this->fileCount = $this->getCountFromSummary($data, 'fileCount');
    }

    private function checkDatabaseIsEmpty(): void
    {
        $nodeCount = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create('MATCH (n) RETURN count(n) as count')
        )->first()->get('count');
        $relationCount = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create('MATCH ()-[r]->() RETURN count(r) as count')
        )->first()->get('count');
        if ($nodeCount > 0 || $relationCount > 0) {
            throw new LogicException('Loading backups into non-empty databases is not supported');
        }
    }
}
