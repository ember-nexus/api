<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Service\DeletionService;
use App\Service\ElementManager;
use App\Type\NodeElement;
use App\Type\RelationElement;
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
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(DeletionService::class)]
class DeletionServiceTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @return array{0: DeletionService, 1: ObjectProphecy<ElementManager>, 2: ObjectProphecy<ClientInterface>}
     */
    private function buildService(): array
    {
        $elementManager = $this->prophesize(ElementManager::class);
        $client = $this->prophesize(ClientInterface::class);
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($client->reveal());

        $service = new DeletionService(
            $elementManager->reveal(),
            $cypherEntityManager->reveal(),
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
        $relation = (new RelationElement())->setId(UuidV4::uuid4())->setType('RELATED');

        [$service, $elementManager, $client] = $this->buildService();
        $client->runStatement(Argument::any())->shouldNotBeCalled();
        $elementManager->delete($relation)->shouldBeCalledOnce()->willReturn($elementManager->reveal());
        $elementManager->flush()->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->delete($relation);
    }

    public function testDeletingANodeWithNoRelationsJustDeletesIt(): void
    {
        $nodeId = UuidV4::uuid4();
        $node = (new NodeElement())->setId($nodeId)->setLabel('Data');

        [$service, $elementManager, $client] = $this->buildService();
        $this->willReturnRelationIds($client, []);
        $elementManager->getRelation(Argument::any())->shouldNotBeCalled();
        $elementManager->delete($node)->shouldBeCalledOnce()->willReturn($elementManager->reveal());
        $elementManager->flush()->shouldBeCalledOnce()->willReturn($elementManager->reveal());

        $service->delete($node);
    }

    public function testDeletingANodeDeletesEachCascadingRelationBeforeTheNodeItself(): void
    {
        $nodeId = UuidV4::uuid4();
        $node = (new NodeElement())->setId($nodeId)->setLabel('Data');
        $relationId1 = UuidV4::uuid4();
        $relationId2 = UuidV4::uuid4();
        $relation1 = (new RelationElement())->setId($relationId1)->setType('OWNS');
        $relation2 = (new RelationElement())->setId($relationId2)->setType('RELATED');

        [$service, $elementManager, $client] = $this->buildService();
        $this->willReturnRelationIds($client, [$relationId1->toString(), $relationId2->toString()]);
        $elementManager->getRelation(Argument::that(fn (UuidInterface $id) => $id->equals($relationId1)))->willReturn($relation1);
        $elementManager->getRelation(Argument::that(fn (UuidInterface $id) => $id->equals($relationId2)))->willReturn($relation2);

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
        $nodeId = UuidV4::uuid4();
        $node = (new NodeElement())->setId($nodeId)->setLabel('Data');
        $relationId = UuidV4::uuid4();

        [$service, $elementManager, $client] = $this->buildService();
        $this->willReturnRelationIds($client, [$relationId->toString()]);
        $elementManager->getRelation(Argument::that(fn (UuidInterface $id) => $id->equals($relationId)))->willReturn(null);

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
        $nodeId = UuidV4::uuid4();
        $node = (new NodeElement())->setId($nodeId)->setLabel('Data');
        $relationId = UuidV4::uuid4();
        $relation = (new RelationElement())->setId($relationId)->setType('RELATED');

        [$service, $elementManager, $client] = $this->buildService();
        // simulates a self-referencing relation matching twice despite the query's DISTINCT (e.g. a future query
        // change losing it)
        $this->willReturnRelationIds($client, [$relationId->toString(), $relationId->toString()]);
        $elementManager->getRelation(Argument::that(fn (UuidInterface $id) => $id->equals($relationId)))
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
