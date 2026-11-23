<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command\Cron;

use App\Command\Cron\ExpireTokensCommand;
use App\Service\CronExecutionGateService;
use App\Service\CronTimeBudgetService;
use App\Service\DeletionService;
use App\Service\ElementManager;
use App\Type\Uuid;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(ExpireTokensCommand::class)]
class ExpireTokensCommandTest extends TestCase
{
    use ProphecyTrait;

    public function testTokensWhichCanNotBeLoadedDoNotMakeTheDeletionFetchTheSamePageAgain(): void
    {
        $ids = array_map(static fn (): string => Uuid::generate()->toString(), range(1, 150));
        sort($ids, SORT_STRING);

        $pagesRequested = [];
        $summary = null;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::type(Statement::class))->will(function (array $args) use ($ids, &$pagesRequested, &$summary) {
            /** @var Statement $statement */
            $statement = $args[0];
            if (str_contains($statement->getText(), 'SET t.state')) {
                return new SummarizedResult($summary, [new CypherMap(['count' => 0])]);
            }
            $lastId = $statement->getParameters()['lastId'];
            $pagesRequested[] = $lastId;
            $rows = array_slice(array_values(array_filter($ids, static fn (string $id): bool => $id > $lastId)), 0, 100);

            return new SummarizedResult($summary, array_map(static fn (string $id): CypherMap => new CypherMap(['id' => $id]), $rows));
        });
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($client->reveal());

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::any())->willReturn(null);
        $elementManager->flush()->willReturn($elementManager->reveal());

        $gate = $this->prophesize(CronExecutionGateService::class);
        $gate->shouldSkipExecution()->willReturn(false);
        $budget = $this->prophesize(CronTimeBudgetService::class);
        $budget->getDeadline()->willReturn(time() + 600);
        $configuration = $this->prophesize(EmberNexusConfiguration::class);
        $configuration->getTokenDeleteExpiredTokensAutomaticallyInSeconds()->willReturn(3600);
        $deletionService = $this->prophesize(DeletionService::class);
        $deletionService->delete(Argument::any())->shouldNotBeCalled();

        $command = new ExpireTokensCommand(
            $gate->reveal(),
            $budget->reveal(),
            $configuration->reveal(),
            $cypherEntityManager->reveal(),
            $elementManager->reveal(),
            $deletionService->reveal()
        );
        $tester = new CommandTester($command);

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        // first page starts at the beginning, second page after the last id of the first page, and is the last one
        $this->assertSame(['', $ids[99]], $pagesRequested);
    }
}
