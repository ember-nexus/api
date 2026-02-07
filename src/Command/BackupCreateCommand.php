<?php

declare(strict_types=1);

namespace App\Command;

use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Service\ElementManager;
use App\Service\ElementToRawService;
use App\Service\FileService;
use App\Style\EmberNexusStyle;
use Exception;
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
    private bool $ndjson = false;

    private EmberNexusStyle $io;

    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private FilesystemOperator $backupStorage,
        private ElementToRawService $elementToRawService,
        private ParameterBagInterface $bag,
        private FileService $fileService,
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
            'ndjson',
            null,
            InputOption::VALUE_NEGATABLE,
            'Saves multiple JSON documents in a few .ndjson files.',
            false
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->backupName = $this->checkBackupName($input->getArgument('name'));
        $this->prettyPrint = $input->getOption('pretty');
        $this->ndjson = $input->getOption('ndjson');
        if ($this->prettyPrint && $this->ndjson) {
            throw new Exception('Pretty print and ndjson are mutually exclusive.');
        }
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
        $this->io->writeln('Currently not implemented.');
        // todo: implement, and add flag to enable file backup (disabled by default?, incl. prominent warning?)
        // if backup, then only storage bucket - elements in the upload bucket would time out anyways
        $this->io->stopSection(sprintf(
            'Successfully backed up <info>%d</info> files.',
            $this->fileCount
        ));
    }

    private function getNodePath(UuidInterface $nodeId): string
    {
        $levels = (int) ceil(log($this->nodeCount, 256));

        return sprintf(
            '%s/node/%s.json',
            $this->backupName,
            $this->fileService->uuidToNestedFolderStructure($nodeId, $levels)
        );
    }

    private function getRelationPath(UuidInterface $relationId): string
    {
        $levels = (int) ceil(log($this->relationCount, 256));

        return sprintf(
            '%s/relation/%s.json',
            $this->backupName,
            $this->fileService->uuidToNestedFolderStructure($relationId, $levels)
        );
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

        if ($this->backupStorage->directoryExists($backupName)) {
            throw new LogicException(sprintf('Backup with name %s already exists', $backupName));
        }

        return $backupName;
    }

    private function createBackupFolders(): void
    {
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
