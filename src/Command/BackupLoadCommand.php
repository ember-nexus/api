<?php

namespace App\Command;

use App\DependencyInjection\DeactivatableTraceableEventDispatcher;
use App\Exception\LogicException;
use App\Service\ElementManager;
use App\Service\RawToElementService;
use App\Style\EonStyle;
use Laudis\Neo4j\Databags\Statement;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[AsCommand(name: 'backup:load')]
class BackupLoadCommand extends Command
{
    private string $backupName;
    private int $relationCount = 0;
    private int $fileCount = 0;
    private int $nodeCount = 0;
    private int $pageSize = 250;

    public function __construct(
        private ElementManager $elementManager,
        private CypherEntityManager $cypherEntityManager,
        private FilesystemOperator $backupStorage,
        private RawToElementService $rawToElementService,
        private ?EventDispatcherInterface $eventDispatcher
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'Name of the backup');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EonStyle($input, $output);

        $this->checkDatabaseIsEmpty();

        if ($this->eventDispatcher instanceof DeactivatableTraceableEventDispatcher) {
            $this->eventDispatcher->deactivate();
        }

        $this->backupName = $this->checkBackupName($input->getArgument('name'));

        $this->loadSummary();

        $this->loadNodes();
        $this->loadRelations();

        return Command::SUCCESS;
    }

    private function loadNodes(): void
    {
        $progressBar = $this->io->createProgressBar($this->nodeCount);
        $progressBar->display();
        $nodeFiles = $this->backupStorage->listContents($this->backupName.'/node/', true);
        $pageCount = 0;
        $totalCount = 0;
        foreach ($nodeFiles as $nodeFile) {
            if (!$nodeFile->isFile()) {
                continue;
            }
            $data = \Safe\json_decode($this->backupStorage->read($nodeFile->path()), true);
            $nodeElement = $this->rawToElementService->rawToElement($data);
            unset($data);
            $this->elementManager->create($nodeElement);
            ++$pageCount;
            if ($pageCount >= $this->pageSize) {
                $this->elementManager->flush();
                $progressBar->advance($pageCount);
                $totalCount += $pageCount;
                $pageCount = 0;
            }
        }
        $this->elementManager->flush();
        $progressBar->advance($pageCount);
        $progressBar->clear();
        $totalCount += $pageCount;
        $this->io->writeln(sprintf(
            'Loaded %d nodes',
            $totalCount
        ));
    }

    private function loadRelations(): void
    {
        $progressBar = $this->io->createProgressBar($this->relationCount);
        $progressBar->display();
        $relationFiles = $this->backupStorage->listContents($this->backupName.'/relation/', true);
        $totalCount = 0;
        $pageCount = 0;
        foreach ($relationFiles as $relationFile) {
            if (!$relationFile->isFile()) {
                continue;
            }
            $data = \Safe\json_decode($this->backupStorage->read($relationFile->path()), true);
            $relationElement = $this->rawToElementService->rawToElement($data);
            unset($data);
            $this->elementManager->create($relationElement);
            ++$pageCount;
            if ($pageCount >= $this->pageSize) {
                $this->elementManager->flush();
                $progressBar->advance($pageCount);
                $totalCount += $pageCount;
                $pageCount = 0;
            }
        }
        $this->elementManager->flush();
        $progressBar->advance($pageCount);
        $progressBar->clear();
        $totalCount += $pageCount;
        $this->io->writeln(sprintf(
            'Loaded %d relations',
            $totalCount
        ));
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
