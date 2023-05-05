<?php

namespace App\Command;

use App\Style\EmberNexusStyle;
use Laudis\Neo4j\Databags\Statement;
use Predis\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 */
#[AsCommand(name: 'database:drop')]
class DatabaseDropCommand extends Command
{
    private EmberNexusStyle $io;

    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private MongoEntityManager $mongoEntityManager,
        private ElasticEntityManager $elasticEntityManager,
        private Client $redisClient
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NEGATABLE,
            'If enabled, command will not ask for manual confirmation.',
            false
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Database Drop');

        if (!$input->getOption('force')) {
            /**
             * @var QuestionHelper $helper
             */
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Are you sure you want to drop all databases? [y/N]: ', false);
            if (!$helper->ask($input, $output, $question)) {
                $this->io->writeln('Aborted dropping databases.');

                return self::FAILURE;
            }
        }

        $this->deleteCypher();

        $this->deleteMongo();

        $this->deleteObjectStorage();

        $this->deleteElastic();

        $this->deleteRedis();

        $this->deleteRabbitMQ();

        $this->io->success('Command ended successfully.');

        return Command::SUCCESS;
    }

    private function deleteCypher(): void
    {
        $this->io->startSection('Task 1 of 6: Cypher');
        $this->io->writeln('Deleting Cypher data...');
        $this->cypherEntityManager->getClient()->runStatement(
            Statement::create('MATCH (n) DETACH DELETE n')
        );
        $this->io->stopSection('Successfully deleted cypher data.');
    }

    private function deleteMongo(): void
    {
        $this->io->startSection('Task 2 of 6: MongoDB');
        $this->io->writeln('Deleting Mongo data...');
        $mongoDatabase = $this->mongoEntityManager->getDatabase();
        if ($mongoDatabase) {
            $this->mongoEntityManager->getClient()->dropDatabase($mongoDatabase);
        }
        $this->io->stopSection('Successfully deleted Mongo data.');
    }

    private function deleteObjectStorage(): void
    {
        $this->io->startSection('Task 3 of 6: Object Storage');
        $this->io->writeln('Deleting object storage data...');
        // todo
        $this->io->stopSection('Object storage is currently not implemented, nothing to delete.');
    }

    private function deleteElastic(): void
    {
        $this->io->startSection('Task 4 of 6: Elastic Search');
        $this->io->writeln('Deleting Elastic data...');
        $rawIndices = $this->elasticEntityManager->getClient()->cat()->indices(['index' => '*'])->asString();
        $rawIndices = explode("\n", $rawIndices);
        $indices = [];
        foreach ($rawIndices as $rawIndex) {
            $parts = explode(' ', $rawIndex);
            if (count($parts) >= 2) {
                $indices[] = $parts[2];
            }
        }
        foreach ($indices as $index) {
            $this->elasticEntityManager->getClient()->indices()->delete(['index' => $index]);
        }
        $this->io->stopSection('Successfully deleted Elastic data.');
    }

    private function deleteRedis(): void
    {
        $this->io->startSection('Task 5 of 6: Redis');
        $this->io->writeln('Deleting Redis data...');
        $this->redisClient->flushdb();
        $this->io->stopSection('Successfully deleted Redis data.');
    }

    private function deleteRabbitMQ(): void
    {
        $this->io->startSection('Task 6 of 6: RabbitMQ');
        $this->io->writeln('Deleting RabbitMQ data...');
        // todo
        $this->io->stopSection('RabbitMQ is currently not implemented, nothing to delete.');
    }
}
