<?php

namespace App\Command;

use App\Service\ElementManager;
use App\Service\ElementToRawService;
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
        private ParameterBagInterface $bag
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

        $this->io->success('Backup finished successfully.');

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
            'Found %d nodes.',
            $this->nodeCount
        ));
        $progressBar = $this->io->createProgressBar($this->nodeCount);
        $progressBar->display();
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
                $nodeIds[] = Uuid::fromString($rawNodeId->get('n.id'));
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
            $progressBar->advance(count($nodeIds));
            $progressBar->display();
        }
        $progressBar->clear();
        $this->io->stopSection(sprintf(
            'Successfully backed up %d nodes.',
            $this->nodeCount
        ));
    }

    private function backupRelations(): void
    {
        $this->io->startSection('Step 2 of 3: Backing up Relations');
        $this->io->writeln(sprintf(
            'Found %d relations.',
            $this->relationCount
        ));
        $progressBar = $this->io->createProgressBar($this->relationCount);
        $progressBar->display();
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
                $relationIds[] = Uuid::fromString($rawRelationId->get('r.id'));
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
            $progressBar->advance(count($relationIds));
            $progressBar->display();
        }
        $progressBar->clear();
        $this->io->stopSection(sprintf(
            'Successfully backed up %d relations.',
            $this->relationCount
        ));
    }

    private function backupFiles(): void
    {
        $this->io->startSection('Step 3 of 3: Backing up Files');
        $this->io->writeln('Currently not implemented.');
        $this->io->stopSection(sprintf(
            'Successfully backed up %d files.',
            $this->fileCount
        ));
    }

    private function getNodePath(UuidInterface $nodeUuid): string
    {
        $nodeUuidAsHex = $nodeUuid->getHex()->toString();
        $folders = ceil(log($this->nodeCount, 256));
        $folderParts = [];
        for ($i = 0; $i < $folders; ++$i) {
            $folderParts[] = substr($nodeUuidAsHex, 2 * $i, 2);
        }

        return sprintf(
            '%s/node/%s/%s.json',
            $this->backupName,
            implode('/', $folderParts),
            $nodeUuid->toString()
        );
    }

    private function getRelationPath(UuidInterface $relationUuid): string
    {
        $relationUuidAsHex = $relationUuid->getHex()->toString();
        $folders = ceil(log($this->relationCount, 256));
        $folderParts = [];
        for ($i = 0; $i < $folders; ++$i) {
            $folderParts[] = substr($relationUuidAsHex, 2 * $i, 2);
        }

        return sprintf(
            '%s/relation/%s/%s.json',
            $this->backupName,
            implode('/', $folderParts),
            $relationUuid->toString()
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

        // todo remove comment block
        //        if ($this->backupStorage->directoryExists($backupName)) {
        //            throw new LogicException(sprintf(
        //                "Backup with name %s already exists",
        //                $backupName
        //            ));
        //        }

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
        $this->nodeCount = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create('MATCH (n) RETURN count(n) as count')
        )->first()->get('count');
        $this->relationCount = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create('MATCH ()-[r]->() RETURN count(r) as count')
        )->first()->get('count');
    }
}
