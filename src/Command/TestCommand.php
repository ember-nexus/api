<?php

declare(strict_types=1);

namespace App\Command;

use App\Search\CypherPathSearchStep;
use App\Search\CypherSearchStep;
use App\Search\ElasticsearchSearchStep;
use App\Search\ElementHydrationStep;
use App\Style\EmberNexusStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 */
#[AsCommand(name: 'test', description: 'Test')]
class TestCommand extends Command
{
    private OutputStyle $io;

    public function __construct(
        private CypherSearchStep $cypherSearchStep,
        private CypherPathSearchStep $cypherPathSearchStep,
        private ElasticsearchSearchStep $elasticsearchSearchStep,
        private ElementHydrationStep $elementHydrationStep,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Test');

        $globalParameters = [];

        //        $steps = [
        //            [
        //                'type' => 'cypher',
        //                'query' => 'MATCH (n:Data)-[:OWNS]->(:Data) WITH n.id AS elementId, n.created AS created ORDER BY created DESC LIMIT 5 RETURN collect(elementId) as elementIds',
        //                'parameters' => [],
        //            ],
        //            [
        //                'type' => 'elementHydration',
        //                'query' => null,
        //                'parameters' => [],
        //            ],
        //        ];

        //        $steps = [
        //            [
        //                'type' => 'elasticsearch',
        //                'query' => [
        //                    'match' => [
        //                        'scenario' => 'general'
        //                    ]
        //                ],
        //                'parameters' => [],
        //            ],
        //            [
        //                'type' => 'elementHydration',
        //                'query' => null,
        //                'parameters' => [],
        //            ],
        //        ];

        //        $steps = [
        //            [
        //                'type' => 'elementHydration',
        //                'query' => null,
        //                'parameters' => [
        //                    'elementIds' => ['9edb178e-1b4a-4518-a9d3-0fa97e6d1007']
        //                ],
        //            ],
        //        ];

        $steps = [
            [
                'type' => 'cypherPath',
                'query' => 'MATCH path=((:Data)-[:OWNS]->(:Data)-[:OWNS]->(:Data)) RETURN path LIMIT 2',
                'parameters' => [],
            ],
        ];

        $stepRunners = [
            'cypher' => $this->cypherSearchStep,
            'cypherPath' => $this->cypherPathSearchStep,
            'elementHydration' => $this->elementHydrationStep,
            'elasticsearch' => $this->elasticsearchSearchStep,
        ];

        $previousResults = [];
        foreach ($steps as $step) {
            $parameters = [...$previousResults, ...$step['parameters'], ...$globalParameters];
            $previousResults = $stepRunners[$step['type']]->executeStep($step['query'], $parameters)->getResults();
        }
        $results = $previousResults;

        //        $results = $this->cypherSearchStep->executeStep('MATCH (n:Data)-[:OWNS]->(:Data) WITH n.id AS elementId, n.created AS created ORDER BY created DESC LIMIT 10 RETURN collect(elementId) as elementIds', []);
        //        $results = $this->elementHydrationStep->executeStep(null, [
        //            'elementIds' => [
        //                '2d270f7b-0ced-4830-a1fe-9bbfdede8043'
        //            ]
        //        ]);

        //        $this->io->writeln(json_encode($results->getResults(), JSON_PRETTY_PRINT));
        //        $this->io->writeln(json_encode($results->getDebugData(), JSON_PRETTY_PRINT));
        $this->io->writeln(json_encode($results, JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }
}
