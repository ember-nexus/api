<?php

declare(strict_types=1);

namespace App\Command;

use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Service\ElementManager;
use App\Service\ElementService;
use App\Service\ElementToRawService;
use App\Service\FileService;
use App\Service\S3Service;
use App\Style\EmberNexusStyle;
use Laudis\Neo4j\Databags\Statement;
use League\Flysystem\FilesystemOperator;
use LogicException;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Safe\DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

use function Safe\gethostname;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'backup:create', description: 'Creates a new backup.')]
class BackupCreateCommand extends Command
{
    private int $nodeCount = 0;
    private int $relationCount = 0;
    private int $fileCount = 0;
    private string $backupName = '';
    private int $pageSize = 10;
    private bool $prettyPrint = false;
    private bool $exportFiles = true;

    private EmberNexusStyle $io;

    /**
     * @SuppressWarnings("PHPMD.ExcessiveParameterList")
     */
    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private FilesystemOperator $backupStorage,
        private ElementToRawService $elementToRawService,
        private ElementService $elementService,
        private ParameterBagInterface $bag,
        private FileService $fileService,
        private S3Service $s3Service,
        private FileOperationFactory $fileOperationFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'name',
            InputArgument::OPTIONAL,
            'Name of the backup, defaults to the current timestamp.',
            (new DateTime())->format('YmdHis')
        );
        $this->addOption(
            'pretty',
            'p',
            InputOption::VALUE_NEGATABLE,
            'Activates pretty print of JSON.',
            false
        );
        $this->addOption(
            'no-files',
            null,
            InputOption::VALUE_NEGATABLE,
            'Disable file export.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->backupName = $this->checkBackupName($input->getArgument('name'));
        $this->prettyPrint = $input->getOption('pretty');
        $this->exportFiles = !$input->getOption('no-files');
        $this->io->title('Backup Create');
        $this->createBackupFolders();
        $this->initCount();

        $this->backupNodes();
        $this->backupRelations();
        $this->backupFiles();
        $this->writeSummary();

        $this->io->finalMessage('Backup finished successfully.');

        return Command::SUCCESS;
    }

    private function writeSummary(): void
    {
        $data = [
            'backupCreated' => (new DateTime())->format('Y-m-d H:i:s e'),
            'nodeCount' => $this->nodeCount,
            'relationCount' => $this->relationCount,
            'fileCount' => $this->fileCount,
            'hostname' => gethostname(),
            'version' => $this->bag->get('version'),
        ];
        $this->backupStorage->write(sprintf(
            '/%s/summary.json',
            $this->backupName
        ),
            \Safe\json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );
    }

    private function backupNodes(): void
    {
        $this->io->startSection('Step 1 of 3: Backing up Nodes');
        $this->io->writeln(sprintf(
            'Found <info>%d</info> nodes.',
            $this->nodeCount
        ));
        $progressBar = $this->io->createProgressBarInInteractiveTerminal($this->nodeCount);
        $progressBar?->display();
        $nextPage = true;
        $currentPage = 0;
        while ($nextPage) {
            $rawNodeIds = $this->cypherEntityManager->getClient()->runStatement(
                Statement::create(
                    'MATCH (n) RETURN n.id SKIP $skip LIMIT $limit',
                    [
                        'skip' => $currentPage * $this->pageSize,
                        'limit' => $this->pageSize,
                    ]
                )
            );
            if (count($rawNodeIds) < $this->pageSize) {
                $nextPage = false;
            }
            $nodeIds = [];
            foreach ($rawNodeIds->toArray() as $rawNodeId) {
                $rawNodeIdContent = $rawNodeId->get('n.id');
                if (!is_string($rawNodeIdContent)) {
                    throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property n.id as string, not %s.', get_debug_type($rawNodeIdContent))); // @codeCoverageIgnore
                }
                $nodeIds[] = Uuid::fromString($rawNodeIdContent);
            }

            foreach ($nodeIds as $nodeId) {
                $node = $this->elementManager->getNode($nodeId);
                if (null === $node) {
                    throw new LogicException('Node can not be null');
                }
                $data = $this->elementToRawService->elementToRaw($node);
                $json = \Safe\json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | ($this->prettyPrint ? JSON_PRETTY_PRINT : 0));
                $path = $this->getNodePath($nodeId);
                $this->backupStorage->write($path, $json);
            }

            ++$currentPage;
            $progressBar?->advance(count($nodeIds));
            $progressBar?->display();
        }
        $progressBar?->clear();
        $this->io->stopSection(sprintf(
            'Successfully backed up <info>%d</info> nodes.',
            $this->nodeCount
        ));
    }

    private function backupRelations(): void
    {
        $this->io->startSection('Step 2 of 3: Backing up Relations');
        $this->io->writeln(sprintf(
            'Found <info>%d</info> relations.',
            $this->relationCount
        ));
        $progressBar = $this->io->createProgressBarInInteractiveTerminal($this->relationCount);
        $progressBar?->display();
        $nextPage = true;
        $currentPage = 0;
        while ($nextPage) {
            $rawRelationIds = $this->cypherEntityManager->getClient()->runStatement(
                Statement::create(
                    'MATCH ()-[r]-() RETURN r.id SKIP $skip LIMIT $limit',
                    [
                        'skip' => $currentPage * $this->pageSize,
                        'limit' => $this->pageSize,
                    ]
                )
            );
            if (count($rawRelationIds) < $this->pageSize) {
                $nextPage = false;
            }
            $relationIds = [];
            foreach ($rawRelationIds->toArray() as $rawRelationId) {
                $rawRelationIdContent = $rawRelationId->get('r.id');
                if (!is_string($rawRelationIdContent)) {
                    throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property r.id as string, not %s.', get_debug_type($rawRelationIdContent))); // @codeCoverageIgnore
                }
                $relationIds[] = Uuid::fromString($rawRelationIdContent);
            }

            foreach ($relationIds as $relationId) {
                $relation = $this->elementManager->getRelation($relationId);
                if (null === $relation) {
                    throw new LogicException('Relation can not be null');
                }
                $data = $this->elementToRawService->elementToRaw($relation);
                $json = \Safe\json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | ($this->prettyPrint ? JSON_PRETTY_PRINT : 0));
                $path = $this->getRelationPath($relationId);
                $this->backupStorage->write($path, $json);
            }

            ++$currentPage;
            $progressBar?->advance(count($relationIds));
            $progressBar?->display();
        }
        $progressBar?->clear();
        $this->io->stopSection(sprintf(
            'Successfully backed up <info>%d</info> relations.',
            $this->relationCount
        ));
    }

    private function backupFiles(): void
    {
        $this->io->startSection('Step 3 of 3: Backing up Files');

        if (false === $this->exportFiles) {
            $this->io->stopSection('File backup skipped.');

            return;
        }

        $rawFileElements = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                'OPTIONAL MATCH (n) WHERE n.file '.
                'OPTIONAL MATCH ()-[r]->() WHERE r.file '.
                'WITH coalesce(n, r) AS element '.
                'WHERE element.file '.
                'RETURN element.id'
            )
        );

        $fileElementIds = [];
        foreach ($rawFileElements->toArray() as $row) {
            $fileElementIds[] = $row->get('element.id');
        }

        $this->fileCount = count($fileElementIds);

        $this->io->writeln(sprintf(
            'Found <info>%d</info> files.',
            $this->fileCount
        ));

        if (0 === $this->fileCount) {
            $this->io->stopSection('File backup skipped.');

            return;
        }

        $progressBar = $this->io->createProgressBarInInteractiveTerminal($this->fileCount);
        $progressBar?->display();

        foreach ($fileElementIds as $rawElementId) {
            if (!is_string($rawElementId)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property element.id as string, not %s.', get_debug_type($rawElementId))); // @codeCoverageIgnore
            }
            $elementId = Uuid::fromString($rawElementId);
            $element = $this->elementManager->getElement($elementId);

            if (null === $element) {
                $progressBar?->advance();
                continue;
            }

            $fileOperation = $this->fileOperationFactory->createFileOperationFromElement($element);
            $resource = $this->s3Service->getFileAsResource($fileOperation);
            $extension = $this->elementService->getFileNameExtension($element);

            $this->backupStorage->writeStream(
                $this->getFilePath($elementId, $extension),
                $resource
            );

            $progressBar?->advance();
        }

        $progressBar?->clear();
        $this->io->stopSection(sprintf(
            'Successfully backed up <info>%d</info> files.',
            $this->fileCount
        ));
    }

    private function getNodePath(UuidInterface $nodeId): string
    {
        $levels = max(0, (int) ceil(log($this->nodeCount, 256)) - 1);

        return sprintf(
            '%s/node/%s.json',
            $this->backupName,
            $this->fileService->uuidToNestedFolderStructure($nodeId, $levels)
        );
    }

    private function getRelationPath(UuidInterface $relationId): string
    {
        $levels = max(0, (int) ceil(log($this->relationCount, 256)) - 1);

        return sprintf(
            '%s/relation/%s.json',
            $this->backupName,
            $this->fileService->uuidToNestedFolderStructure($relationId, $levels)
        );
    }

    private function getFilePath(UuidInterface $elementId, string $extension): string
    {
        $levels = max(0, (int) ceil(log($this->fileCount, 256)) - 1);

        return sprintf(
            '%s/file/%s.%s',
            $this->backupName,
            $this->fileService->uuidToNestedFolderStructure($elementId, $levels),
            $extension
        );
    }

    private function checkBackupName(string $backupName): string
    {
        $backupName = trim($backupName);

        $forbiddenNames = ['', '.', '..'];
        if (in_array($backupName, $forbiddenNames)) {
            throw new LogicException(sprintf("Backup name can not be '%s'", $backupName));
        }

        if ($this->backupStorage->directoryExists($backupName)) {
            throw new LogicException(sprintf('Backup with name %s already exists', $backupName));
        }

        return $backupName;
    }

    private function createBackupFolders(): void
    {
        $this->io->writeln(sprintf("Creating backup <info>%s</info> in folder <info>./var/backup</info>\n", $this->backupName));

        $this->backupStorage->createDirectory($this->backupName);
        $this->backupStorage->createDirectory($this->backupName.'/node');
        $this->backupStorage->createDirectory($this->backupName.'/relation');
        $this->backupStorage->createDirectory($this->backupName.'/file');
    }

    private function initCount(): void
    {
        $rawNodeCount = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create('MATCH (n) RETURN count(n) as count')
        )->first()->get('count');
        if (!is_int($rawNodeCount)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property count as int, not %s.', get_debug_type($rawNodeCount))); // @codeCoverageIgnore
        }
        $this->nodeCount = $rawNodeCount;
        $rawRelationCount = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create('MATCH ()-[r]->() RETURN count(r) as count')
        )->first()->get('count');
        if (!is_int($rawRelationCount)) {
            throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property count as int, not %s.', get_debug_type($rawRelationCount))); // @codeCoverageIgnore
        }
        $this->relationCount = $rawRelationCount;
    }
}
