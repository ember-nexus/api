<?php

declare(strict_types=1);

namespace App\Command;

use App\Style\EmberNexusStyle;
use Laudis\Neo4j\Databags\Statement;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'test', description: 'Test')]
class TestCommand extends Command
{
    private OutputStyle $io;

    public function __construct(
        private CypherEntityManager $cypherEntityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Test');

        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH (parent {id: \$parentId})\n".
                    "MATCH (parent)-[:OWNS]->(children)\n".
                    "MATCH (parent)-[relations]->(children)\n".
                    "WITH children, relations\n".
                    "LIMIT %d\n".
                    "WITH children, relations\n".
                    "ORDER BY children.id, relations.id\n".
                    "WITH COLLECT([children.id, children.updated]) + COLLECT([relations.id, relations.updated]) AS allTuples\n".
                    "WITH allTuples\n".
                    "UNWIND allTuples AS tuple\n".
                    "WITH tuple ORDER BY tuple[0]\n".
                    'RETURN COLLECT(tuple) AS sortedTuples;',
                    100
                ),
                [
                    'parentId' => '7b80b203-2b82-40f5-accd-c7089fe6114e',
                ]
            )
        );

        print_r($result->toArray());

        return Command::SUCCESS;
    }
}
