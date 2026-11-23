<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\DeletionService;
use App\Service\ElementManager;
use App\Service\ElementTypeService;
use App\Type\NodeElement;
use App\Type\RelationElement;
use App\Type\Uuid;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(DeletionService::class)]
class DeletionServiceTest extends TestCase
{
    use ProphecyTrait;

    private function buildElementTypeService(?string $nodeLabel = 'Data'): ElementTypeService
    {
        $elementTypeService = $this->prophesize(ElementTypeService::class);
        $elementTypeService->resolveNodeLabel(Argument::any())->willReturn($nodeLabel);

        return $elementTypeService->reveal();
    }

    /**
     * @return array{0: DeletionService, 1: ObjectProphecy<ElementManager>, 2: ObjectProphecy<ClientInterface>}
     */
    private function buildService(?string $nodeLabel = 'Data'): array
    {
        $elementManager = $this->prophesize(ElementManager::class);
        $client = $this->prophesize(ClientInterface::class);
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($client->reveal());

        $service = new DeletionService(
            $elementManager->reveal(),
            $cypherEntityManager->reveal(),
            $this->buildElementTypeService($nodeLabel),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        );

        return [$service, $elementManager, $client];
    }

    /**
     * @param string[] $relationIds
     */
    private function willReturnRelationIds(ObjectProphecy $client, array $relationIds): void
    {
        $summaryReference = null;
        $rows = array_map(static fn (string $id) => new CypherMap(['relationId' => $id]), $relationIds);
        $client->runStatement(Argument::type(Statement::class))->willReturn(new SummarizedResult($summaryReference, $rows));
    }

    public function testDeletingARelationNeverQueriesCypherAndJustDeletesIt(): void
    {
        $relation = (new RelationElement())->setId(Uuid::generate())->setType('RELATED');

        [$service, $elementManager, $client] = $this->buildService();
        $client->runStatement(Argument::any())->shouldNotBeCalled();
        $elementManager->delete($relation)->shouldBeCalledOnce()->willReturn($elementManager->reveal());
        $elementManager->flush()->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->delete($relation);
    }

    public function testDeletingANodeWithNoRelationsJustDeletesIt(): void
    {
        $nodeId = Uuid::generate();
        $node = (new NodeElement())->setId($nodeId)->setLabel('Data');

        [$service, $elementManager, $client] = $this->buildService();
        $this->willReturnRelationIds($client, []);
        $elementManager->getRelation(Argument::any())->shouldNotBeCalled();
        $elementManager->delete($node)->shouldBeCalledOnce()->willReturn($elementManager->reveal());
        $elementManager->flush()->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->delete($node);
    }

    public function testDeletingANodeWithUnresolvedLabelSkipsTheRelationQuery(): void
    {
        $node = (new NodeElement())->setId(Uuid::generate())->setLabel('Data');

        [$service, $elementManager, $client] = $this->buildService(null);
        $client->runStatement(Argument::any())->shouldNotBeCalled();
        $elementManager->delete($node)->shouldBeCalledOnce()->willReturn($elementManager->reveal());
        $elementManager->flush()->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->delete($node);
    }

    public function testRelationQueryUsesTheEscapedResolvedLabel(): void
    {
        $node = (new NodeElement())->setId(Uuid::generate())->setLabel('Da`ta');

        [$service, $elementManager, $client] = $this->buildService('Da`ta');
        $summaryReference = null;
        $client->runStatement(Argument::that(
            fn (Statement $statement) => str_contains($statement->getText(), 'MATCH (node:`Da``ta` {id: $nodeId})-[relation]-()')
        ))->shouldBeCalledOnce()->willReturn(new SummarizedResult($summaryReference, []));
        $elementManager->delete($node)->willReturn($elementManager->reveal());
        $elementManager->flush()->willReturn($elementManager->reveal());

        $service->delete($node);
    }

    public function testDeletingANodeDeletesEachCascadingRelationBeforeTheNodeItself(): void
    {
        $nodeId = Uuid::generate();
        $node = (new NodeElement())->setId($nodeId)->setLabel('Data');
        $relationId1 = Uuid::generate();
        $relationId2 = Uuid::generate();
        $relation1 = (new RelationElement())->setId($relationId1)->setType('OWNS');
        $relation2 = (new RelationElement())->setId($relationId2)->setType('RELATED');

        [$service, $elementManager, $client] = $this->buildService();
        $this->willReturnRelationIds($client, [$relationId1->toString(), $relationId2->toString()]);
        $elementManager->getRelation(Argument::that(fn (Uuid $id) => $id->equals($relationId1)))->willReturn($relation1);
        $elementManager->getRelation(Argument::that(fn (Uuid $id) => $id->equals($relationId2)))->willReturn($relation2);

        $deletedInOrder = [];
        $elementManager->delete(Argument::any())->will(function (array $args) use (&$deletedInOrder, $elementManager) {
            $deletedInOrder[] = $args[0];

            return $elementManager->reveal();
        });
        $elementManager->flush()->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->delete($node);

        $this->assertSame([$relation1, $relation2, $node], $deletedInOrder);
    }

    public function testDeletingANodeSkipsARelationThatIsAlreadyGoneByTheTimeItIsHydrated(): void
    {
        $nodeId = Uuid::generate();
        $node = (new NodeElement())->setId($nodeId)->setLabel('Data');
        $relationId = Uuid::generate();

        [$service, $elementManager, $client] = $this->buildService();
        $this->willReturnRelationIds($client, [$relationId->toString()]);
        $elementManager->getRelation(Argument::that(fn (Uuid $id) => $id->equals($relationId)))->willReturn(null);

        $deleted = [];
        $elementManager->delete(Argument::any())->will(function (array $args) use (&$deleted, $elementManager) {
            $deleted[] = $args[0];

            return $elementManager->reveal();
        });
        $elementManager->flush()->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->delete($node);

        $this->assertSame([$node], $deleted);
    }

    public function testDeletingANodeDoesNotDeleteTheSameDuplicatedRelationIdTwice(): void
    {
        $nodeId = Uuid::generate();
        $node = (new NodeElement())->setId($nodeId)->setLabel('Data');
        $relationId = Uuid::generate();
        $relation = (new RelationElement())->setId($relationId)->setType('RELATED');

        [$service, $elementManager, $client] = $this->buildService();
        // simulates a self-referencing relation matching twice despite the query's DISTINCT (e.g. a future query
        // change losing it)
        $this->willReturnRelationIds($client, [$relationId->toString(), $relationId->toString()]);
        $elementManager->getRelation(Argument::that(fn (Uuid $id) => $id->equals($relationId)))
            ->shouldBeCalledOnce()
            ->willReturn($relation);

        $deleted = [];
        $elementManager->delete(Argument::any())->will(function (array $args) use (&$deleted, $elementManager) {
            $deleted[] = $args[0];

            return $elementManager->reveal();
        });
        $elementManager->flush()->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->delete($node);

        $this->assertSame([$relation, $node], $deleted);
    }

    public function testDeletingANodeWithoutAnIdSkipsTheRelationQuery(): void
    {
        $node = new NodeElement();

        [$service, $elementManager, $client] = $this->buildService();
        $client->runStatement(Argument::any())->shouldNotBeCalled();
        $elementManager->delete($node)->shouldBeCalledOnce()->willReturn($elementManager->reveal());
        $elementManager->flush()->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->delete($node);
    }
}
