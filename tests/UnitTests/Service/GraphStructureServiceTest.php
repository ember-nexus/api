<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Factory\Type\RedisKeyFactory;
use App\Service\GraphStructureService;
use Exception;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Contracts\TransactionInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use RuntimeException;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(GraphStructureService::class)]
class GraphStructureServiceTest extends TestCase
{
    use ProphecyTrait;

    private const string KEY = 'graph-structure:';

    /** @var ObjectProphecy<RedisClient> */
    private ObjectProphecy $redis;
    private int $databaseReads = 0;
    /** @var list<string> */
    private array $databaseLabels = ['Data', 'User'];

    protected function setUp(): void
    {
        $this->redis = $this->prophesize(RedisClient::class);
        $this->redis->get(self::KEY)->willReturn(null);
        $this->redis->set(Argument::cetera())->willReturn(null);
        $this->redis->del(Argument::any())->willReturn(1);
        $this->databaseReads = 0;
    }

    private function create(): GraphStructureService
    {
        $test = $this;
        $transaction = $this->prophesize(TransactionInterface::class);
        $transaction->run(Argument::that(static fn (string $query) => str_contains($query, 'db.labels')))->will(function () use ($test) {
            $summary = null;

            return new SummarizedResult($summary, array_map(static fn (string $label) => new CypherMap(['label' => $label]), $test->databaseLabels));
        });
        $transaction->run(Argument::that(static fn (string $query) => str_contains($query, 'db.relationshipTypes')))->will(function () {
            $summary = null;

            return new SummarizedResult($summary, [new CypherMap(['relationshipType' => 'OWNS'])]);
        });
        $revealedTransaction = $transaction->reveal();
        $client = $this->prophesize(ClientInterface::class);
        $client->readTransaction(Argument::any())->will(function (array $args) use ($test, $revealedTransaction) {
            ++$test->databaseReads;

            return $args[0]($revealedTransaction);
        });
        $entityManager = $this->prophesize(CypherEntityManager::class);
        $entityManager->getClient()->willReturn($client->reveal());

        return new GraphStructureService($entityManager->reveal(), $this->redis->reveal(), new RedisKeyFactory());
    }

    public function testStructureIsReadFromTheDatabaseOnceAndCachedInRedis(): void
    {
        $this->redis->set(self::KEY, '{"nodeTypes":["Data","User"],"relationTypes":["OWNS"]}', 'EX', 3600)->shouldBeCalledOnce();
        $service = $this->create();

        $this->assertSame(['Data', 'User'], $service->getNodeTypes());
        $this->assertSame(['OWNS'], $service->getRelationTypes());
        $this->assertSame('Data', $service->getNodeTypeFromElasticIndex('node_data'));
        $this->assertSame(2, $this->databaseReads);
    }

    public function testStructureComesFromRedisWithoutTouchingTheDatabase(): void
    {
        $this->redis->get(self::KEY)->willReturn('{"nodeTypes":["Cached"],"relationTypes":["REL"]}');
        $service = $this->create();

        $this->assertSame(['Cached'], $service->getNodeTypes());
        $this->assertSame(['REL'], $service->getRelationTypes());
        $this->assertSame('REL', $service->getRelationTypeFromElasticIndex('relation_rel'));
        $this->assertSame(0, $this->databaseReads);
    }

    public function testUnknownIndexOfCachedStructureReloadsOnceFromTheDatabase(): void
    {
        $this->redis->get(self::KEY)->willReturn('{"nodeTypes":["Cached"],"relationTypes":["REL"]}');
        $service = $this->create();

        $this->assertSame('Data', $service->getNodeTypeFromElasticIndex('node_data'));
        $this->assertSame(2, $this->databaseReads);
        // fresh from the database now: a miss is a real miss
        $this->assertNull($service->getNodeTypeFromElasticIndex('node_missing'));
        $this->assertNull($service->getRelationTypeFromElasticIndex('relation_missing'));
        $this->assertSame(2, $this->databaseReads);
    }

    public function testBrokenRedisIsIgnored(): void
    {
        $this->redis->get(self::KEY)->willThrow(new RuntimeException('down'));
        $this->redis->set(Argument::cetera())->willThrow(new RuntimeException('down'));
        $this->redis->del(Argument::any())->willThrow(new RuntimeException('down'));
        $service = $this->create();

        $this->assertSame(['Data', 'User'], $service->getNodeTypes());
        $service->noteType(true, 'New');
        $service->applyPendingInvalidation();
        $this->assertSame(['Data', 'User'], $service->getNodeTypes());
    }

    public function testKnownTypesDoNotInvalidateTheCache(): void
    {
        $this->redis->del(Argument::any())->shouldNotBeCalled();
        $service = $this->create();

        $service->noteType(true, 'Data');
        $service->noteType(false, 'OWNS');
        $service->applyPendingInvalidation();
    }

    public function testNewTypeDropsTheCacheOnlyWhenApplied(): void
    {
        $this->redis->del([self::KEY])->shouldBeCalledOnce();
        $service = $this->create();

        $service->noteType(false, 'BRAND_NEW');
        $this->assertSame(2, $this->databaseReads);
        $service->applyPendingInvalidation();
        $service->applyPendingInvalidation();

        $this->databaseLabels = ['Data', 'User', 'Added'];
        $this->assertSame(['Data', 'User', 'Added'], $service->getNodeTypes());
        $this->assertSame(4, $this->databaseReads);
    }

    public function testResetForgetsEverythingLoaded(): void
    {
        $service = $this->create();
        $service->getNodeTypes();
        $service->reset();
        $service->getNodeTypes();

        $this->assertSame(4, $this->databaseReads);
    }

    public function testInvalidTypePrefixesThrow(): void
    {
        $service = $this->create();

        $this->expectException(Exception::class);
        $service->getNodeTypeFromElasticIndex('relation_x');
    }

    public function testElasticIndexOfOtherKindsMapsToNull(): void
    {
        $this->assertNull($this->create()->getTypeFromElasticIndex('something'));
    }

    public function testSetStructureIsUsedAndCachedWithoutTouchingTheDatabase(): void
    {
        $this->redis->set(self::KEY, '{"nodeTypes":["A","B"],"relationTypes":["R"]}', 'EX', 3600)->shouldBeCalledOnce();
        $service = $this->create();

        $service->setStructure(['A', 'B'], ['R']);

        $this->assertSame(['A', 'B'], $service->getNodeTypes());
        $this->assertSame(['R'], $service->getRelationTypes());
        $this->assertSame(0, $this->databaseReads);
    }

    public function testRefreshDropsTheCacheAndReadsTheDatabase(): void
    {
        $this->redis->del([self::KEY])->shouldBeCalledOnce();
        $service = $this->create();
        $service->setStructure(['Old'], []);

        $service->refresh();

        $this->assertSame(['Data', 'User'], $service->getNodeTypes());
        $this->assertSame(2, $this->databaseReads);
    }

    public function testRefreshWorksWithBrokenRedis(): void
    {
        $this->redis->del(Argument::any())->willThrow(new RuntimeException('down'));
        $service = $this->create();

        $service->refresh();

        $this->assertSame(['Data', 'User'], $service->getNodeTypes());
    }
}
