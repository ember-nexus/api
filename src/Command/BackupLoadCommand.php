<?php

declare(strict_types=1);

namespace App\Command;

use App\DependencyInjection\DeactivatableTraceableEventDispatcher;
use App\EventSystem\EntityManager\Event\ElementUpdateAfterBackupLoadEvent;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Helper\Regex;
use App\Service\AppStateService;
use App\Service\ElementManager;
use App\Service\RawToElementService;
use App\Service\StorageUtilService;
use App\Style\EmberNexusStyle;
use App\Type\AppStateType;
use AsyncAws\S3\Input\PutObjectRequest;
use AsyncAws\S3\S3Client;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Databags\Statement;
use League\Flysystem\FilesystemOperator;
use LogicException;
use Predis\Client;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'backup:load', description: 'Loads a local backup into the empty database.')]
class BackupLoadCommand extends Command
{
    private string $backupName;
    private int $relationCount = 0;
    /**
     * @phpstan-ignore-next-line
     */
    private int $fileCount = 0;
    private int $nodeCount = 0;
    private int $pageSize = 250;

    private EmberNexusStyle $io;

    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private S3Client $s3Client,
        private StorageUtilService $storageUtilService,
        private Client $redisClient,
        private FilesystemOperator $backupStorage,
        private RawToElementService $rawToElementService,
        private EventDispatcherInterface $eventDispatcher,
        private AppStateService $appStateService,
        private ElasticEntityManager $elasticEntityManager,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of the backup');
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
            $nodeElement = $this->rawToElementService->rawToElement($data);
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
            $relationElement = $this->rawToElementService->rawToElement($data);
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
        if (!preg_match(Regex::UUID_V4, $name)) {
            return false;
        }

        return Uuid::fromString($name);
    }

    private function loadFiles(): void
    {
        $this->io->startSection('Step 3 of 4: Loading Files');
        $progressBar = $this->io->createProgressBarInInteractiveTerminal($this->fileCount);
        $progressBar?->display();
        $files = $this->backupStorage->listContents($this->backupName.'/file/', true);
        $pageCount = 0;
        $totalCount = 0;
        foreach ($files as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->path();
            $fileId = $this->parseFilenameAsUuidFromPath($path);
            if (false === $fileId) {
                continue;
            }
            $element = $this->elementManager->getElement($fileId);
            if (null === $element) {
                $this->io->warning(sprintf(
                    'Found file in backup without corresponding element; can not import file: %s',
                    $path
                ));
                continue;
            }

            // todo: optimize upload for larger files using multipart-upload?
            $resource = $this->backupStorage->readStream($path);
            $this->s3Client->putObject(new PutObjectRequest([
                'Bucket' => $this->emberNexusConfiguration->getFileS3StorageBucket(),
                'Key' => $this->storageUtilService->getStorageBucketKey($fileId),
                'Body' => $resource,
            ]))->resolve();

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
                    throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property n.id as string, not %s.', get_debug_type($rawId))); // @codeCoverageIgnore
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
                    throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property r.id as string, not %s.', get_debug_type($rawId))); // @codeCoverageIgnore
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
