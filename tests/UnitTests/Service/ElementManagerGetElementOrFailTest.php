<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Exception\Client404NotFoundException;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Helper\Neo4jClientHelper;
use App\Service\AccessResultCacheService;
use App\Service\ElementDefragmentizeService;
use App\Service\ElementFragmentizeService;
use App\Service\ElementManager;
use App\Service\ElementTypeService;
use App\Service\GraphStructureService;
use App\Service\Mphf\ElementTypeOverlayService;
use App\Service\PendingSearchDocumentService;
use App\Type\ElementKind;
use App\Type\NodeElement;
use App\Type\RelationElement;
use App\Type\ResolvedType;
use App\Type\Uuid;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

#[Small]
#[CoversClass(ElementManager::class)]
class ElementManagerGetElementOrFailTest extends TestCase
{
    use ProphecyTrait;

    private const string ID = '02IiNr0G8viOVKjbso133HjMjY1fDvYfd7Wub';

    private Client404NotFoundException $notFoundException;

    protected function setUp(): void
    {
        $this->notFoundException = new Client404NotFoundException('some type');
    }

    private function buildElementTypeService(?ResolvedType $resolved, ?string $nodeLabel = null, ?string $relationType = null): ElementTypeService
    {
        $elementTypeService = $this->prophesize(ElementTypeService::class);
        $elementTypeService->resolve(Argument::any())->willReturn($resolved);
        $elementTypeService->resolveNodeLabel(Argument::any())->willReturn($nodeLabel);
        $elementTypeService->resolveRelationType(Argument::any())->willReturn($relationType);

        return $elementTypeService->reveal();
    }

    private function buildElementManager(?ResolvedType $resolved = null, ?ElementTypeService $elementTypeService = null): ElementManager
    {
        $notFoundFactory = $this->prophesize(Client404NotFoundExceptionFactory::class);
        $notFoundFactory->createFromTemplate(Argument::cetera())->willReturn($this->notFoundException);

        $arguments = [
            $this->prophesize(CypherEntityManager::class)->reveal(),
            $this->prophesize(MongoEntityManager::class)->reveal(),
            $this->prophesize(ElasticEntityManager::class)->reveal(),
            $this->prophesize(ElementFragmentizeService::class)->reveal(),
            $this->prophesize(ElementDefragmentizeService::class)->reveal(),
            $this->prophesize(Neo4jClientHelper::class)->reveal(),
            $this->prophesize(EventDispatcherInterface::class)->reveal(),
            new NullLogger(),
            $elementTypeService ?? $this->buildElementTypeService($resolved),
            $this->prophesize(ElementTypeOverlayService::class)->reveal(),
            $this->prophesize(GraphStructureService::class)->reveal(),
            new PendingSearchDocumentService(),
            new AccessResultCacheService(),
            $notFoundFactory->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        ];

        return new class(...$arguments) extends ElementManager {
            public ?NodeElementInterface $node = null;
            public ?RelationElementInterface $relation = null;

            public function getNode(Uuid $id): ?NodeElementInterface
            {
                return $this->node;
            }

            public function getRelation(Uuid $id): ?RelationElementInterface
            {
                return $this->relation;
            }
        };
    }

    public function testReturnsNodeIfItExists(): void
    {
        $node = new NodeElement();
        $elementManager = $this->buildElementManager(new ResolvedType(ElementKind::NODE, 'Data'));
        $elementManager->node = $node;

        $this->assertSame($node, $elementManager->getElementOrFail(Uuid::fromString(self::ID)));
    }

    public function testReturnsRelationIfIdResolvesToARelation(): void
    {
        $relation = new RelationElement();
        $elementManager = $this->buildElementManager(new ResolvedType(ElementKind::RELATION, 'RELATED'));
        $elementManager->relation = $relation;

        $this->assertSame($relation, $elementManager->getElementOrFail(Uuid::fromString(self::ID)));
    }

    public function testThrowsNotFoundIfTheIdDoesNotResolveToAType(): void
    {
        $elementManager = $this->buildElementManager();

        $this->expectExceptionObject($this->notFoundException);

        $elementManager->getElementOrFail(Uuid::fromString(self::ID));
    }

    public function testElementExistsAsksTheTypeServiceAndLoadsNothing(): void
    {
        $elementTypeService = $this->prophesize(ElementTypeService::class);
        $elementTypeService->elementExists(Uuid::fromString(self::ID))->willReturn(true);
        $elementManager = $this->buildElementManager(null, $elementTypeService->reveal());
        // getNode() of the test double returns null, so a lookup of the element itself would not find it
        $this->assertTrue($elementManager->elementExists(Uuid::fromString(self::ID)));
    }

    public function testGetElementReturnsNullIfNothingExists(): void
    {
        $elementManager = $this->buildElementManager();

        $this->assertNull($elementManager->getElement(Uuid::fromString(self::ID)));
    }

    public function testGetElementDoesNotFallBackToTheOtherKindIfResolvedKindIsMissing(): void
    {
        $elementManager = $this->buildElementManager(new ResolvedType(ElementKind::NODE, 'Data'));
        $elementManager->relation = new RelationElement();

        $this->assertNull($elementManager->getElement(Uuid::fromString(self::ID)));
    }

    /**
     * @param ObjectProphecy<ClientInterface> $client
     */
    private function buildRealElementManager(ElementTypeService $elementTypeService, ObjectProphecy $client): ElementManager
    {
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($client->reveal());

        return new ElementManager(
            $cypherEntityManager->reveal(),
            $this->prophesize(MongoEntityManager::class)->reveal(),
            $this->prophesize(ElasticEntityManager::class)->reveal(),
            $this->prophesize(ElementFragmentizeService::class)->reveal(),
            $this->prophesize(ElementDefragmentizeService::class)->reveal(),
            $this->prophesize(Neo4jClientHelper::class)->reveal(),
            $this->prophesize(EventDispatcherInterface::class)->reveal(),
            new NullLogger(),
            $elementTypeService,
            $this->prophesize(ElementTypeOverlayService::class)->reveal(),
            $this->prophesize(GraphStructureService::class)->reveal(),
            new PendingSearchDocumentService(),
            new AccessResultCacheService(),
            $this->prophesize(Client404NotFoundExceptionFactory::class)->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        );
    }

    public function testGetNodeAndGetRelationSkipCypherIfTypeIsUnresolved(): void
    {
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())->shouldNotBeCalled();
        $elementManager = $this->buildRealElementManager($this->buildElementTypeService(null), $client);

        $this->assertNull($elementManager->getNode(Uuid::fromString(self::ID)));
        $this->assertNull($elementManager->getRelation(Uuid::fromString(self::ID)));
    }

    public function testGetNodeQueriesWithTheEscapedResolvedLabel(): void
    {
        $summary = null;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::that(
            fn (Statement $statement) => 'MATCH (node:`Da``ta` {id: $id}) RETURN node' === $statement->getText()
        ))->shouldBeCalledOnce()->willReturn(new SummarizedResult($summary, []));
        $elementManager = $this->buildRealElementManager($this->buildElementTypeService(null, nodeLabel: 'Da`ta'), $client);

        $this->assertNull($elementManager->getNode(Uuid::fromString(self::ID)));
    }

    public function testGetRelationQueriesWithTheEscapedResolvedType(): void
    {
        $summary = null;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::that(
            fn (Statement $statement) => 'MATCH (startNode)-[relation:`REL``X` {id: $id}]->(endNode) RETURN startNode, relation, endNode' === $statement->getText()
        ))->shouldBeCalledOnce()->willReturn(new SummarizedResult($summary, []));
        $elementManager = $this->buildRealElementManager($this->buildElementTypeService(null, relationType: 'REL`X'), $client);

        $this->assertNull($elementManager->getRelation(Uuid::fromString(self::ID)));
    }
}
