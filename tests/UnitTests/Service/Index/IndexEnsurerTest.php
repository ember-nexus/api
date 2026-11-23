<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service\Index;

use App\Service\GraphStructureService;
use App\Service\Index\IndexEnsurer;
use App\Service\Index\IndexNameService;
use App\Service\MaintenanceLockService;
use App\Type\MaintenanceLock;
use App\Type\MaintenanceLockType;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use RuntimeException;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(IndexEnsurer::class)]
#[CoversClass(\App\Service\Index\IndexEnsureResult::class)]
class IndexEnsurerTest extends TestCase
{
    use ProphecyTrait;

    /** @var string[] */
    private array $statements = [];
    /** @var array<int, array<string, mixed>> */
    private array $existingIndices = [];
    private ObjectProphecy $locks;
    private ObjectProphecy $graph;

    protected function setUp(): void
    {
        $this->locks = $this->prophesize(MaintenanceLockService::class);
        $this->graph = $this->prophesize(GraphStructureService::class);
        $this->graph->loadGraphStructure();
        $this->graph->getNodeTypes()->willReturn(['Data', 'User']);
        $this->graph->getRelationTypes()->willReturn(['OWNS']);
    }

    private function create(): IndexEnsurer
    {
        $test = $this;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())->will(function (array $args) use ($test) {
            $text = $args[0]->getText();
            $test->statements[] = $text;
            $summary = null;
            $rows = str_starts_with($text, 'SHOW INDEXES YIELD name, type') || str_starts_with($text, 'SHOW INDEXES YIELD name WHERE')
                ? array_map(static fn (array $row) => new CypherMap($row), $test->existingIndices)
                : [];

            return new SummarizedResult($summary, $rows);
        });
        $entityManager = $this->prophesize(CypherEntityManager::class);
        $entityManager->getClient()->willReturn($client->reveal());

        return new IndexEnsurer($entityManager->reveal(), $this->graph->reveal(), new IndexNameService(), $this->locks->reveal(), new NullLogger());
    }

    /**
     * @return string[]
     */
    private function creations(): array
    {
        return array_values(array_filter($this->statements, static fn (string $statement) => str_starts_with($statement, 'CREATE')));
    }

    public function testCreatesMissingNodeAndRelationIndices(): void
    {
        $result = $this->create()->ensure();

        $this->assertCount(3, $result->getCreated());
        $this->assertCount(3, $this->creations());
        $this->assertStringContainsString('FOR (n:`Data`) ON (n.id)', $this->creations()[0]);
        $this->assertStringContainsString('FOR ()-[r:`OWNS`]-() ON (r.id)', $this->creations()[2]);
        $this->assertStringContainsString('RANGE INDEX', $this->creations()[0]);
        $this->assertStringContainsString('IF NOT EXISTS', $this->creations()[0]);
    }

    public function testKnownStructureIsNotReadFromTheDatabase(): void
    {
        $this->graph->loadGraphStructure()->shouldNotBeCalled();

        $result = $this->create()->ensure(readStructure: false);

        $this->assertCount(3, $result->getCreated());
    }

    public function testKindFilters(): void
    {
        $this->assertCount(2, $this->create()->ensure(relations: false)->getCreated());
        $this->statements = [];
        $this->assertCount(1, $this->create()->ensure(nodes: false)->getCreated());
    }

    public function testExistingAndEquivalentIndicesAreNotRecreated(): void
    {
        $names = new IndexNameService();
        $this->existingIndices = [
            ['name' => $names->getNodeIndexName('Data'), 'entityType' => 'NODE', 'typeName' => 'Data'],
            ['name' => 'handmade', 'entityType' => 'NODE', 'typeName' => 'User'],
        ];

        $result = $this->create()->ensure();

        $this->assertSame([$names->getNodeIndexName('Data')], $result->getExisting());
        $this->assertCount(1, $result->getEquivalent());
        $this->assertStringContainsString('handmade', $result->getEquivalent()[0]);
        $this->assertCount(1, $this->creations());
    }

    public function testDryRunCreatesNothing(): void
    {
        $result = $this->create()->ensure(dryRun: true, awaitSeconds: 5);

        $this->assertCount(3, $result->getCreated());
        $this->assertSame([], $this->creations());
        $this->assertNotContains('CALL db.awaitIndexes($seconds)', $this->statements);
    }

    public function testAwaitsIndicesOnlyIfSomethingWasCreated(): void
    {
        $this->create()->ensure(awaitSeconds: 5);
        $this->assertContains('CALL db.awaitIndexes($seconds)', $this->statements);

        $this->statements = [];
        $this->existingIndices = [];
        $this->graph->getNodeTypes()->willReturn([]);
        $this->graph->getRelationTypes()->willReturn([]);
        $this->create()->ensure(awaitSeconds: 5);
        $this->assertNotContains('CALL db.awaitIndexes($seconds)', $this->statements);
    }

    public function testEnsureWithLockClaimsAndReleases(): void
    {
        $lock = new MaintenanceLock(MaintenanceLockType::DATABASE_INDICES, 't');
        $this->locks->claim(MaintenanceLockType::DATABASE_INDICES)->willReturn($lock);
        $this->locks->isHeld(MaintenanceLockType::DATABASE)->willReturn(false);
        $this->locks->release($lock)->shouldBeCalledOnce()->willReturn(true);

        $result = $this->create()->ensureWithLock();

        $this->assertNotNull($result);
    }

    public function testEnsureWithLockSkipsWhenLockTaken(): void
    {
        $this->locks->claim(MaintenanceLockType::DATABASE_INDICES)->willReturn(null);
        $reason = null;

        $result = $this->create()->ensureWithLock(skipReason: $reason);

        $this->assertNull($result);
        $this->assertStringContainsString('lock:maintenance:database-indices', (string) $reason);
        $this->assertSame([], $this->statements);
    }

    public function testEnsureWithLockSkipsDuringBackupLoad(): void
    {
        $lock = new MaintenanceLock(MaintenanceLockType::DATABASE_INDICES, 't');
        $this->locks->claim(MaintenanceLockType::DATABASE_INDICES)->willReturn($lock);
        $this->locks->isHeld(MaintenanceLockType::DATABASE)->willReturn(true);
        $this->locks->release($lock)->shouldBeCalledOnce()->willReturn(true);
        $reason = null;

        $this->assertNull($this->create()->ensureWithLock(skipReason: $reason));
        $this->assertStringContainsString('backup load or reset', (string) $reason);
    }

    public function testEnsureWithLockHeldByCallerDoesNotClaim(): void
    {
        $this->locks->claim(Argument::any())->shouldNotBeCalled();

        $this->assertNotNull($this->create()->ensureWithLock(lockHeldByCaller: true));
    }

    public function testEnsureWithLockReleasesOnFailure(): void
    {
        $lock = new MaintenanceLock(MaintenanceLockType::DATABASE_INDICES, 't');
        $this->locks->claim(MaintenanceLockType::DATABASE_INDICES)->willReturn($lock);
        $this->locks->isHeld(MaintenanceLockType::DATABASE)->willReturn(false);
        $this->locks->release($lock)->shouldBeCalledOnce()->willReturn(true);
        $this->graph->loadGraphStructure()->willThrow(new RuntimeException('neo4j down'));

        $this->expectException(RuntimeException::class);
        $this->create()->ensureWithLock();
    }

    public function testDropManagedIndices(): void
    {
        $this->existingIndices = [['name' => 'ember_nexus_node_id_data_x'], ['name' => 'ember_nexus_relation_id_owns_y'], ['name' => 5]];

        $dropped = $this->create()->dropManagedIndices();

        $this->assertSame(['ember_nexus_node_id_data_x', 'ember_nexus_relation_id_owns_y'], $dropped);
        $this->assertContains('DROP INDEX `ember_nexus_node_id_data_x` IF EXISTS', $this->statements);
    }
}
