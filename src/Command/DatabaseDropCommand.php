<?php

namespace App\Command;

use Laudis\Neo4j\Databags\Statement;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

#[AsCommand(name: 'database:drop')]
class DatabaseDropCommand extends Command
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private MongoEntityManager $mongoEntityManager,
        private ElasticEntityManager $elasticEntityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Deleting Cypher data...');
        $this->cypherEntityManager->getClient()->runStatement(
            Statement::create('MATCH (n) DETACH DELETE n')
        );
        $output->writeln('Successfully deleted cypher data');

        $output->writeln('Deleting Mongo data..');
        $this->mongoEntityManager->getClient()->dropDatabase($this->mongoEntityManager->getDatabase());
        $output->writeln('Successfully deleted Mongo data');

        $output->writeln('Deleting Elastic data...');
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
        $output->writeln('Successfully deleted Elastic data');

        $output->writeln('Done');

        return Command::SUCCESS;
    }
}
