<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command\Cron;

use App\Command\Cron\UpdateOwnershipCommand;
use App\Service\CronExecutionGateService;
use App\Service\CronTimeBudgetService;
use App\Service\ElementManager;
use App\Service\QueueService;
use App\Service\SearchAccessCalculatorService;
use App\Type\NodeElement;
use App\Type\RabbitMQQueueType;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use LogicException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticDataStructures\Type\Document;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;

#[Small]
#[CoversClass(UpdateOwnershipCommand::class)]
class UpdateOwnershipCommandTest extends TestCase
{
    use ProphecyTrait;

    private function buildCommand(
        bool $isCronDisabled = false,
        ?QueueService $queueService = null,
        ?ElementManager $elementManager = null,
        ?SearchAccessCalculatorService $searchAccessCalculatorService = null,
        ?ElasticEntityManager $elasticEntityManager = null,
        ?CypherEntityManager $cypherEntityManager = null,
        ?CronTimeBudgetService $cronTimeBudgetService = null,
    ): UpdateOwnershipCommand {
        $cronExecutionGateService = $this->prophesize(CronExecutionGateService::class);
        $cronExecutionGateService->shouldSkipExecution()->willReturn($isCronDisabled);

        if (null === $cronTimeBudgetService) {
            $cronTimeBudgetServiceProphecy = $this->prophesize(CronTimeBudgetService::class);
            $cronTimeBudgetServiceProphecy->getDeadline()->willReturn(null);
            $cronTimeBudgetService = $cronTimeBudgetServiceProphecy->reveal();
        }

        return new UpdateOwnershipCommand(
            $cronExecutionGateService->reveal(),
            $cronTimeBudgetService,
            $queueService ?? $this->prophesize(QueueService::class)->reveal(),
            $elementManager ?? $this->prophesize(ElementManager::class)->reveal(),
            $searchAccessCalculatorService ?? $this->prophesize(SearchAccessCalculatorService::class)->reveal(),
            $elasticEntityManager ?? $this->prophesize(ElasticEntityManager::class)->reveal(),
            $cypherEntityManager ?? $this->prophesize(CypherEntityManager::class)->reveal(),
        );
    }

    private function noChildrenCypherEntityManager(): CypherEntityManager
    {
        $noSummary = null;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())->willReturn(new SummarizedResult($noSummary, []));
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($client->reveal());

        return $cypherEntityManager->reveal();
    }

    public function testCommandStopsEarlyIfCronIsDisabled(): void
    {
        $queueService = $this->prophesize(QueueService::class);
        $queueService->consumeQueue(Argument::cetera())->shouldNotBeCalled();

        $command = $this->buildCommand(isCronDisabled: true, queueService: $queueService->reveal());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Cron is disabled', $commandTester->getDisplay());
    }

    public function testCommandFinishesWhenCronIsEnabledAndQueueIsEmpty(): void
    {
        $queueService = $this->prophesize(QueueService::class);
        $queueService->consumeQueue(Argument::is(RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE), Argument::type('callable'), null)
            ->shouldBeCalledOnce()
            ->willReturn(0);

        $command = $this->buildCommand(queueService: $queueService->reveal());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Running interactively; no time limit applied.', $commandTester->getDisplay());
        $this->assertStringContainsString('Processed 0 queue message(s), updated search access of 0 element(s).', $commandTester->getDisplay());
    }

    public function testCommandForwardsNonNullDeadlineToQueueService(): void
    {
        $cronTimeBudgetService = $this->prophesize(CronTimeBudgetService::class);
        $cronTimeBudgetService->getDeadline()->willReturn(1_700_000_000);

        $queueService = $this->prophesize(QueueService::class);
        $queueService->consumeQueue(Argument::is(RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE), Argument::type('callable'), 1_700_000_000)
            ->shouldBeCalledOnce()
            ->willReturn(0);

        $command = $this->buildCommand(queueService: $queueService->reveal(), cronTimeBudgetService: $cronTimeBudgetService->reveal());

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringNotContainsString('Running interactively', $commandTester->getDisplay());
    }

    public function testCommandThrowsIfCronDisabledParameterIsNotBoolean(): void
    {
        $cronExecutionGateService = $this->prophesize(CronExecutionGateService::class);
        $cronExecutionGateService->shouldSkipExecution()->willThrow(
            new LogicException('Expected "isCronDisabled" to be of type boolean, got string.')
        );

        $cronTimeBudgetService = $this->prophesize(CronTimeBudgetService::class);
        $cronTimeBudgetService->getDeadline()->willReturn(null);

        $command = new UpdateOwnershipCommand(
            $cronExecutionGateService->reveal(),
            $cronTimeBudgetService->reveal(),
            $this->prophesize(QueueService::class)->reveal(),
            $this->prophesize(ElementManager::class)->reveal(),
            $this->prophesize(SearchAccessCalculatorService::class)->reveal(),
            $this->prophesize(ElasticEntityManager::class)->reveal(),
            $this->prophesize(CypherEntityManager::class)->reveal(),
        );

        $this->expectException(LogicException::class);
        (new CommandTester($command))->execute([]);
    }

    public function testCommandSkipsMessageIfElementWasAlreadyDeleted(): void
    {
        $elementId = Uuid::fromString('5a5c6b60-1b3b-4e6d-9c0b-6c1b7bb2f9d5');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::any())->willReturn(null);

        $searchAccessCalculatorService = $this->prophesize(SearchAccessCalculatorService::class);
        $searchAccessCalculatorService->calculateSearchAccess(Argument::any())->shouldNotBeCalled();

        $queueService = $this->prophesize(QueueService::class);
        $queueService->consumeQueue(Argument::is(RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE), Argument::type('callable'), Argument::any())
            ->will(function ($args) use ($elementId) {
                /** @var callable $handler */
                $handler = $args[1];
                $handler(['relationId' => null, 'startId' => $elementId->toString(), 'endId' => null]);

                return 1;
            });
        $queueService->publishEvent(Argument::cetera())->shouldNotBeCalled();

        $command = $this->buildCommand(
            queueService: $queueService->reveal(),
            elementManager: $elementManager->reveal(),
            searchAccessCalculatorService: $searchAccessCalculatorService->reveal(),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('updated search access of 0 element(s)', $commandTester->getDisplay());
    }

    public function testCommandDoesNotStoreOrEnqueueChildrenWhenDeltaIsZero(): void
    {
        $elementId = Uuid::fromString('4f4c6b60-1b3b-4e6d-9c0b-6c1b7bb2f9d4');
        $element = (new NodeElement())->setId($elementId)->setLabel('Data');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::that(fn ($id) => $id->toString() === $elementId->toString()))
            ->willReturn($element);

        $searchAccessCalculatorService = $this->prophesize(SearchAccessCalculatorService::class);
        $searchAccessCalculatorService->getIndexForElement($element)->willReturn('node_data');
        $searchAccessCalculatorService->calculateSearchAccess($element)->willReturn([
            'groups' => ['group-a'],
            'users' => ['user-a'],
        ]);

        $existingDocument = (new Document())
            ->setIdentifier($elementId->toString())
            ->setIndex('node_data')
            ->addProperties([
                // same sets, different order, to also prove the comparison is set based rather than order based
                '_groupsWithSearchAccess' => ['group-a'],
                '_usersWithSearchAccess' => ['user-a'],
            ]);

        $elasticEntityManager = $this->prophesize(ElasticEntityManager::class);
        $elasticEntityManager->getOneByIdentifier('node_data', $elementId->toString())->willReturn($existingDocument);
        $elasticEntityManager->merge(Argument::any())->shouldNotBeCalled();
        $elasticEntityManager->flush()->shouldNotBeCalled();

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->shouldNotBeCalled();

        $queueService = $this->prophesize(QueueService::class);
        $queueService->consumeQueue(Argument::is(RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE), Argument::type('callable'), Argument::any())
            ->will(function ($args) use ($elementId) {
                /** @var callable $handler */
                $handler = $args[1];
                $handler(['relationId' => null, 'startId' => $elementId->toString(), 'endId' => null]);

                return 1;
            });
        $queueService->publishEvent(Argument::cetera())->shouldNotBeCalled();

        $command = $this->buildCommand(
            queueService: $queueService->reveal(),
            elementManager: $elementManager->reveal(),
            searchAccessCalculatorService: $searchAccessCalculatorService->reveal(),
            elasticEntityManager: $elasticEntityManager->reveal(),
            cypherEntityManager: $cypherEntityManager->reveal(),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('updated search access of 0 element(s)', $commandTester->getDisplay());
    }

    /**
     * The parent's child is never fetched or recalculated within this same handler call - it is only queued as a
     * brand new, standalone message (see {@see UpdateOwnershipCommand::recalculateElementAndQueueChildren()}), to
     * be recalculated whenever that message is drained, possibly in a later run.
     */
    public function testCommandQueuesChildrenAsNewMessagesInsteadOfRecalculatingThemInProcess(): void
    {
        $parentId = Uuid::fromString('4f4c6b60-1b3b-4e6d-9c0b-6c1b7bb2f9d4');
        $childId = Uuid::fromString('11111111-1111-1111-1111-111111111111');
        $parent = (new NodeElement())->setId($parentId)->setLabel('Data');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::that(fn ($id) => $id->toString() === $parentId->toString()))
            ->shouldBeCalledTimes(1)
            ->willReturn($parent);
        $elementManager->getElement(Argument::that(fn ($id) => $id->toString() === $childId->toString()))
            ->shouldNotBeCalled();

        $searchAccessCalculatorService = $this->prophesize(SearchAccessCalculatorService::class);
        $searchAccessCalculatorService->getIndexForElement($parent)->willReturn('node_data');
        $searchAccessCalculatorService->calculateSearchAccess($parent)->willReturn([
            'groups' => [],
            'users' => ['new-user'],
        ]);

        $elasticEntityManager = $this->prophesize(ElasticEntityManager::class);
        $elasticEntityManager->getOneByIdentifier('node_data', $parentId->toString())->willReturn(null);
        $elasticEntityManager->merge(Argument::any())->shouldBeCalledTimes(1)->willReturn($elasticEntityManager->reveal());
        $elasticEntityManager->flush()->shouldBeCalledTimes(1)->willReturn($elasticEntityManager->reveal());

        $noSummary = null;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())
            ->shouldBeCalledTimes(1)
            ->willReturn(new SummarizedResult($noSummary, [
                new CypherMap(['id' => $childId->toString()]),
            ]));
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($client->reveal());

        $queueService = $this->prophesize(QueueService::class);
        $queueService->consumeQueue(Argument::is(RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE), Argument::type('callable'), Argument::any())
            ->will(function ($args) use ($parentId) {
                /** @var callable $handler */
                $handler = $args[1];
                $handler(['relationId' => null, 'startId' => $parentId->toString(), 'endId' => null]);

                return 1;
            });
        $queueService->publishEvent(
            RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE,
            ['elementId' => $childId->toString()]
        )->shouldBeCalledTimes(1);

        $command = $this->buildCommand(
            queueService: $queueService->reveal(),
            elementManager: $elementManager->reveal(),
            searchAccessCalculatorService: $searchAccessCalculatorService->reveal(),
            elasticEntityManager: $elasticEntityManager->reveal(),
            cypherEntityManager: $cypherEntityManager->reveal(),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('updated search access of 1 element(s)', $commandTester->getDisplay());
    }

    public function testCommandHandlesAQueuedChildMessageJustLikeAnyOtherElement(): void
    {
        $childId = Uuid::fromString('11111111-1111-1111-1111-111111111111');
        $child = (new NodeElement())->setId($childId)->setLabel('Data');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::that(fn ($id) => $id->toString() === $childId->toString()))
            ->willReturn($child);

        $searchAccessCalculatorService = $this->prophesize(SearchAccessCalculatorService::class);
        $searchAccessCalculatorService->getIndexForElement($child)->willReturn('node_data');
        $searchAccessCalculatorService->calculateSearchAccess($child)->willReturn(['groups' => [], 'users' => ['some-user']]);

        $elasticEntityManager = $this->prophesize(ElasticEntityManager::class);
        $elasticEntityManager->getOneByIdentifier('node_data', $childId->toString())->willReturn(null);
        $elasticEntityManager->merge(Argument::any())->shouldBeCalledTimes(1)->willReturn($elasticEntityManager->reveal());
        $elasticEntityManager->flush()->shouldBeCalledTimes(1)->willReturn($elasticEntityManager->reveal());

        $queueService = $this->prophesize(QueueService::class);
        $queueService->consumeQueue(Argument::is(RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE), Argument::type('callable'), Argument::any())
            ->will(function ($args) use ($childId) {
                /** @var callable $handler */
                $handler = $args[1];
                $handler(['elementId' => $childId->toString()]);

                return 1;
            });

        $command = $this->buildCommand(
            queueService: $queueService->reveal(),
            elementManager: $elementManager->reveal(),
            searchAccessCalculatorService: $searchAccessCalculatorService->reveal(),
            elasticEntityManager: $elasticEntityManager->reveal(),
            cypherEntityManager: $this->noChildrenCypherEntityManager(),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('updated search access of 1 element(s)', $commandTester->getDisplay());
    }

    public function testCommandRecalculatesGroupChildrenDirectlyForIsInGroupEvents(): void
    {
        // IS_IN_GROUP does not change the group's own direct accessors (it grants the member the group's rights,
        // not access to the group node itself), so its own delta is always zero; the group's children must still be
        // recalculated directly rather than only cascading on a non-zero delta at the group itself
        $groupId = Uuid::fromString('44444444-4444-4444-4444-444444444444');
        $memberId = Uuid::fromString('55555555-5555-5555-5555-555555555555');
        $childId = Uuid::fromString('66666666-6666-6666-6666-666666666666');
        $group = (new NodeElement())->setId($groupId)->setLabel('Group');
        $member = (new NodeElement())->setId($memberId)->setLabel('User');
        $child = (new NodeElement())->setId($childId)->setLabel('Data');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::that(fn ($id) => $id->toString() === $groupId->toString()))->willReturn($group);
        $elementManager->getElement(Argument::that(fn ($id) => $id->toString() === $memberId->toString()))->willReturn($member);
        $elementManager->getElement(Argument::that(fn ($id) => $id->toString() === $childId->toString()))->willReturn($child);

        $searchAccessCalculatorService = $this->prophesize(SearchAccessCalculatorService::class);
        $searchAccessCalculatorService->getIndexForElement($group)->willReturn('node_group');
        $searchAccessCalculatorService->getIndexForElement($member)->willReturn('node_user');
        $searchAccessCalculatorService->getIndexForElement($child)->willReturn('node_data');
        // the group's own delta is zero (its own accessors are unaffected by who is a member)...
        $searchAccessCalculatorService->calculateSearchAccess($group)->willReturn(['groups' => [], 'users' => []]);
        $searchAccessCalculatorService->calculateSearchAccess($member)->willReturn(['groups' => [], 'users' => []]);
        // ...but a child the group owns can still gain the new member as an accessor
        $searchAccessCalculatorService->calculateSearchAccess($child)->willReturn(['groups' => [], 'users' => [$memberId->toString()]]);

        $elasticEntityManager = $this->prophesize(ElasticEntityManager::class);
        $elasticEntityManager->getOneByIdentifier('node_group', $groupId->toString())->willReturn(null);
        $elasticEntityManager->getOneByIdentifier('node_user', $memberId->toString())->willReturn(null);
        $elasticEntityManager->getOneByIdentifier('node_data', $childId->toString())->willReturn(null);
        $elasticEntityManager->merge(Argument::any())->shouldBeCalledTimes(1)->willReturn($elasticEntityManager->reveal());
        $elasticEntityManager->flush()->shouldBeCalledTimes(1)->willReturn($elasticEntityManager->reveal());

        $noSummary = null;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())
            ->will(fn ($args) => new SummarizedResult($noSummary, $groupId->toString() === $args[0]->getParameters()['parentId']
                ? [new CypherMap(['id' => $childId->toString()])]
                : []));
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($client->reveal());

        $queueService = $this->prophesize(QueueService::class);
        $queueService->publishEvent(Argument::cetera())->shouldNotBeCalled();
        $queueService->consumeQueue(Argument::is(RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE), Argument::type('callable'), Argument::any())
            ->will(function ($args) use ($groupId, $memberId) {
                /** @var callable $handler */
                $handler = $args[1];
                $handler([
                    'relationId' => null,
                    'relationType' => 'IS_IN_GROUP',
                    'startId' => $memberId->toString(),
                    'endId' => $groupId->toString(),
                ]);

                return 1;
            });

        $command = $this->buildCommand(
            queueService: $queueService->reveal(),
            elementManager: $elementManager->reveal(),
            searchAccessCalculatorService: $searchAccessCalculatorService->reveal(),
            elasticEntityManager: $elasticEntityManager->reveal(),
            cypherEntityManager: $cypherEntityManager->reveal(),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('updated search access of 1 element(s)', $commandTester->getDisplay());
    }

    public function testCommandDeduplicatesIdsOfTheSameMessagePointingAtTheSameElement(): void
    {
        // relationId, startId and endId of the same message all point at the same element: it must be recomputed
        // only once per message, not three times
        $elementId = Uuid::fromString('22222222-2222-2222-2222-222222222222');
        $element = (new NodeElement())->setId($elementId)->setLabel('Data');

        $elementManager = $this->prophesize(ElementManager::class);
        $elementManager->getElement(Argument::that(fn ($id) => $id->toString() === $elementId->toString()))
            ->shouldBeCalledTimes(1)
            ->willReturn($element);

        $searchAccessCalculatorService = $this->prophesize(SearchAccessCalculatorService::class);
        $searchAccessCalculatorService->getIndexForElement($element)->willReturn('node_data');
        $searchAccessCalculatorService->calculateSearchAccess($element)
            ->shouldBeCalledTimes(1)
            ->willReturn(['groups' => [], 'users' => []]);

        $elasticEntityManager = $this->prophesize(ElasticEntityManager::class);
        $elasticEntityManager->getOneByIdentifier('node_data', $elementId->toString())->willReturn(null);

        $queueService = $this->prophesize(QueueService::class);
        $queueService->consumeQueue(Argument::is(RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE), Argument::type('callable'), Argument::any())
            ->will(function ($args) use ($elementId) {
                /** @var callable $handler */
                $handler = $args[1];
                $handler([
                    'relationId' => $elementId->toString(),
                    'startId' => $elementId->toString(),
                    'endId' => $elementId->toString(),
                ]);

                return 1;
            });

        $command = $this->buildCommand(
            queueService: $queueService->reveal(),
            elementManager: $elementManager->reveal(),
            searchAccessCalculatorService: $searchAccessCalculatorService->reveal(),
            elasticEntityManager: $elasticEntityManager->reveal(),
            cypherEntityManager: $this->noChildrenCypherEntityManager(),
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
