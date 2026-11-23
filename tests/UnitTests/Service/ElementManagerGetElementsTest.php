<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\NodeElementInterface;
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
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Laudis\Neo4j\Types\Node as LaudisNode;
use Laudis\Neo4j\Types\Relationship as LaudisRelationship;
use LogicException;
use MongoDB\BSON\Int64;
use MongoDB\Client as MongoClient;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\CursorInterface;
use MongoDB\Driver\Server;
use MongoDB\Model\BSONDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use Syndesi\CypherDataStructures\Type\Node;
use Syndesi\CypherDataStructures\Type\Relation;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoDataStructures\Contract\DocumentInterface;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

#[Small]
#[CoversClass(ElementManager::class)]
class ElementManagerGetElementsTest extends TestCase
{
    use ProphecyTrait;

    private Uuid $nodeA;
    private Uuid $nodeB;
    private Uuid $relation;
    private Uuid $unknown;
    /** @var list<string> */
    private array $queries = [];
    /** @var array<string, list<string>> */
    private array $mongoQueries = [];

    protected function setUp(): void
    {
        $this->nodeA = Uuid::generate();
        $this->nodeB = Uuid::generate();
        $this->relation = Uuid::generate();
        $this->unknown = Uuid::generate();
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedPrivateMethod")
     */
    private function laudisNode(Uuid $id): LaudisNode
    {
        return new LaudisNode(1, new CypherList(['Data']), new CypherMap(['id' => $id->toString()]), '1');
    }

    private function buildElementManager(bool $onlyUnknown = false): ElementManager
    {
        $test = $this;
        $types = [
            $this->nodeA->toString() => new ResolvedType(ElementKind::NODE, 'Data'),
            $this->nodeB->toString() => new ResolvedType(ElementKind::NODE, 'Data'),
            $this->relation->toString() => new ResolvedType(ElementKind::RELATION, 'OWNS'),
            $this->unknown->toString() => null,
        ];
        $elementTypeService = $this->prophesize(ElementTypeService::class);
        $elementTypeService->resolveMany(Argument::any())->willReturn($onlyUnknown ? [$this->unknown->toString() => null] : $types);

        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())->will(function (array $args) use ($test) {
            /** @var Statement $statement */
            $statement = $args[0];
            $test->queries[] = $statement->getText();
            $summary = null;
            if (str_contains($statement->getText(), 'RETURN node')) {
                // the database answers in its own order, the element manager must restore the requested one
                return new SummarizedResult($summary, [
                    new CypherMap(['node' => $test->laudisNode($test->nodeB)]),
                    new CypherMap(['node' => $test->laudisNode($test->nodeA)]),
                ]);
            }

            return new SummarizedResult($summary, [new CypherMap([
                'startNode' => $test->laudisNode($test->nodeA),
                'endNode' => $test->laudisNode($test->nodeB),
                'relation' => new LaudisRelationship(5, 1, 1, 'OWNS', new CypherMap(['id' => $test->relation->toString()]), '5'),
            ])]);
        });
        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($client->reveal());

        $neo4jClientHelper = $this->prophesize(Neo4jClientHelper::class);
        $neo4jClientHelper->getNodeFromLaudisNode(Argument::any())->will(
            static fn (array $args) => (new Node())->addProperties($args[0]->getProperties()->toArray())
        );
        $neo4jClientHelper->getRelationFromLaudisRelation(Argument::cetera())->will(
            static fn (array $args) => (new Relation())->setType('OWNS')->addProperties($args[0]->getProperties()->toArray())
        );
        $defragmentize = $this->prophesize(ElementDefragmentizeService::class);
        $defragmentize->defragmentize(Argument::any(), Argument::any())->will(
            static function (array $args) use ($test) {
                /** @var Node|Relation $fragment */
                $fragment = $args[0];
                $id = Uuid::fromString((string) $fragment->getProperty('id'));
                $document = $args[1];
                $element = $fragment instanceof Node ? (new NodeElement())->setLabel('Data') : (new RelationElement())->setType('OWNS');
                $element->setId($id);
                if ($document instanceof DocumentInterface && $id->equals($test->nodeA)) {
                    $element->addProperty('fromMongo', $document->getProperty('field'));
                }

                return $element;
            }
        );

        $collection = $this->prophesize(Collection::class);
        $collection->find(Argument::any())->will(function (array $args) use ($test) {
            $test->mongoQueries[] = $args[0]['_id']['$in'];

            return new class([new BSONDocument(['_id' => $test->nodeA->toString(), 'field' => 'value'])]) implements CursorInterface {
                private int $position = 0;

                /** @param list<BSONDocument> $documents */
                public function __construct(private array $documents)
                {
                }

                public function current(): object|array|null
                {
                    return $this->documents[$this->position] ?? null;
                }

                public function key(): ?int
                {
                    return $this->position;
                }

                public function next(): void
                {
                    ++$this->position;
                }

                public function rewind(): void
                {
                    $this->position = 0;
                }

                public function valid(): bool
                {
                    return isset($this->documents[$this->position]);
                }

                public function getId(): Int64
                {
                    return new Int64(0);
                }

                public function getServer(): Server
                {
                    throw new LogicException('not needed');
                }

                public function isDead(): bool
                {
                    return false;
                }

                public function setTypeMap(array $typemap): void
                {
                }

                public function toArray(): array
                {
                    return $this->documents;
                }
            };
        });
        $database = $this->prophesize(Database::class);
        $database->selectCollection(Argument::any())->willReturn($collection->reveal());
        $mongoClient = $this->prophesize(MongoClient::class);
        $mongoClient->selectDatabase('db')->willReturn($database->reveal());
        $mongoEntityManager = $this->prophesize(MongoEntityManager::class);
        $mongoEntityManager->getClient()->willReturn($mongoClient->reveal());
        $mongoEntityManager->getDatabase()->willReturn('db');

        return new ElementManager(
            $cypherEntityManager->reveal(),
            $mongoEntityManager->reveal(),
            $this->prophesize(ElasticEntityManager::class)->reveal(),
            $this->prophesize(ElementFragmentizeService::class)->reveal(),
            $defragmentize->reveal(),
            $neo4jClientHelper->reveal(),
            $this->prophesize(EventDispatcherInterface::class)->reveal(),
            new NullLogger(),
            $elementTypeService->reveal(),
            $this->prophesize(ElementTypeOverlayService::class)->reveal(),
            $this->prophesize(GraphStructureService::class)->reveal(),
            new PendingSearchDocumentService(),
            new AccessResultCacheService(),
            $this->prophesize(\App\Factory\Exception\Client404NotFoundExceptionFactory::class)->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        );
    }

    public function testLoadsAllElementsWithOneQueryPerTypeAndKeepsTheRequestedOrder(): void
    {
        $elements = $this->buildElementManager()->getElements([$this->relation, $this->nodeA, $this->unknown, $this->nodeB]);

        $this->assertSame(
            [$this->relation->toString(), $this->nodeA->toString(), $this->nodeB->toString()],
            array_map(static fn (object $element) => $element->getId()?->toString(), $elements)
        );
        $this->assertInstanceOf(NodeElementInterface::class, $elements[1]);
        $this->assertSame('value', $elements[1]->getProperty('fromMongo'));
        $this->assertCount(2, $this->queries);
        $this->assertStringContainsString('UNWIND $ids AS id MATCH (node:`Data` {id: id}) RETURN node', $this->queries[0]);
        $this->assertStringContainsString('MATCH (startNode)-[relation:`OWNS` {id: id}]->(endNode)', $this->queries[1]);
        $this->assertSame([[$this->nodeA->toString(), $this->nodeB->toString()], [$this->relation->toString()]], $this->mongoQueries);
    }

    public function testWithoutExistingElementsNothingIsLoaded(): void
    {
        $elements = $this->buildElementManager(true)->getElements([$this->unknown]);

        $this->assertSame([], $elements);
    }
}
