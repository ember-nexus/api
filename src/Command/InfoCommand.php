<?php

namespace App\Command;

use App\Style\EmberNexusStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

#[AsCommand(name: 'info')]
class InfoCommand extends Command
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private MongoEntityManager $mongoEntityManager,
        private ElasticEntityManager $elasticEntityManager,
        private ParameterBagInterface $bag
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new EmberNexusStyle($input, $output);

        $io->title('Info');

        $io->definitionList(
            ['Version' => $this->bag->get('version')],
            'Cypher database',
            ['Vendor' => '-'],
            ['Version' => '-'],
            'Mongo database',
            ['Version' => '-'],
            'Elastic database',
            ['Version' => ''],
            'Object storage',
            ['Vendor' => ''],
            ['Version' => '']
        );
        $output->writeln('Done');

        return Command::SUCCESS;
    }
}
