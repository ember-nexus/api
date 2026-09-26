<?php

declare(strict_types=1);

namespace App\Command;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\DependencyInjection\DeactivatableTraceableEventDispatcher;
use App\EventSystem\EntityManager\Event\ElementUpdateAfterBackupLoadEvent;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\UploadFileOperationFactory;
use App\Helper\Regex;
use App\Service\AppStateService;
use App\Service\ElementManager;
use App\Service\FileHashService;
use App\Service\FileSizeLimitService;
use App\Service\RawToElementService;
use App\Service\S3Service;
use App\Style\EmberNexusStyle;
use App\Type\AppStateType;
use Laudis\Neo4j\Databags\Statement;
use League\Flysystem\FilesystemOperator;
use LogicException;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
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
 *
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
#[AsCommand(name: 'backup:load', description: 'Loads a local backup into the empty database.')]
class BackupLoadCommand extends Command
{
    private string $backupName;
    private int $relationCount = 0;
    private int $fileCount = 0;
    private int $nodeCount = 0;
    private int $pageSize = 250;
    private bool $skipVerify = false;

    private EmberNexusStyle $io;

    /**
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
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
        private UploadFileOperationFactory $uploadFileOperationFactory,
        private FileSizeLimitService $fileSizeLimitService,
        private FileHashService $fileHashService,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of the backup');
        $this->addOption('skip-verify', null, InputOption::VALUE_NONE, "Skip verifying file contents against their stored 'file.hash' values");
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

        $this->backupName = $this->checkBackupName($input->getArgument('name'));
        $this->skipVerify = (bool) $input->getOption('skip-verify');

        $this->loadSummary();

        $this->loadNodes();
        $this->loadRelations();
        $this->loadFiles();
        $this->afterBackupTasks();

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
            $data = \Safe\json_decode($this->backupStorage->read($nodeFile->path()), true);
            $nodeElement = $this->rawToElementService->rawToElement($data, true);
            unset($data);
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
        foreach ($relationFiles as $relationFile) {
            if (!$relationFile->isFile()) {
                continue;
            }
            if (!str_ends_with($relationFile->path(), '.json')) {
                continue;
            }
            $data = \Safe\json_decode($this->backupStorage->read($relationFile->path()), true);
            $relationElement = $this->rawToElementService->rawToElement($data, true);
            unset($data);
            $this->elementManager->create($relationElement);
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
            'Loaded <info>%d</info> relations.',
            $totalCount
        ));
    }

    private function parseFilenameAsUuidFromPath(string $path): false|UuidInterface
    {
        $filename = basename($path);
        $parts = explode('.', $filename, 2);
        if (2 !== count($parts)) {
            return false;
        }
        $name = $parts[0];
        if (!\Safe\preg_match(Regex::UUID_V4, $name)) {
            return false;
        }

        return Uuid::fromString($name);
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
    private function loadFile(string $path, UuidInterface $fileId): bool
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
            $uploadFileOperation = $this->uploadFileOperationFactory->createUploadFileOperationFromElementAndResource($element, $resource, $contentLength);
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
     * Verifies the backup file against the `file.hash.<algorithm>` values stored on its element.
     *
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
            $fileId = $this->parseFilenameAsUuidFromPath($path);
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

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
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
                $id = UuidV4::fromString($rawId);
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
                'MATCH ()-[r]->() RETURN r.id ORDER BY r.id SKIP $skip LIMIT $limit;',
                [
                    'skip' => $page * $pageSize,
                    'limit' => $pageSize,
                ]
            ));
            if (0 === count($res)) {
                $endReached = true;
            }
            foreach ($res as $row) {
                $rawId = $row->get('r.id');
                if (!is_string($rawId)) {
                    throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property r.id as string, not %s.', get_debug_type($rawId))); // @codeCoverageIgnore
                }
                $id = UuidV4::fromString($rawId);
                $element = $this->elementManager->getRelation($id);
                if ($element) {
                    $event = new ElementUpdateAfterBackupLoadEvent($element);
                    $this->eventDispatcher->dispatch($event);
                }
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

    private function loadSummary(): void
    {
        if (!$this->backupStorage->fileExists($this->backupName.'/summary.json')) {
            throw new LogicException(sprintf("Backup with the name '%s' does not contain a summary.json", $this->backupName));
        }
        $data = \Safe\json_decode($this->backupStorage->read($this->backupName.'/summary.json'), true);
        $nodeCount = 0;
        if (array_key_exists('nodeCount', $data)) {
            $nodeCount = (int) $data['nodeCount'];
        }
        $this->nodeCount = $nodeCount;
        $relationCount = 0;
        if (array_key_exists('relationCount', $data)) {
            $relationCount = (int) $data['relationCount'];
        }
        $this->relationCount = $relationCount;
        $fileCount = 0;
        if (array_key_exists('fileCount', $data)) {
            $fileCount = (int) $data['fileCount'];
        }
        $this->fileCount = $fileCount;
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
