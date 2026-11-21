<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service\Mphf;

use App\Factory\Type\RedisKeyFactory;
use App\Service\GraphStructureService;
use App\Service\Index\IndexEnsurer;
use App\Service\MaintenanceLockService;
use App\Service\Mphf\ElementIdFilterService;
use App\Service\Mphf\ElementTypeOverlayService;
use App\Service\Mphf\MphfBuildResult;
use App\Service\Mphf\MphfBuildService;
use App\Service\Mphf\MphfStorageService;
use App\Service\Mphf\SystemBucketService;
use App\Type\MaintenanceLock;
use App\Type\MaintenanceLockType;
use Laudis\Neo4j\Contracts\ClientInterface;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\NullLogger;
use RuntimeException;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(MphfBuildService::class)]
#[CoversClass(MphfBuildResult::class)]
class MphfBuildServiceTest extends TestCase
{
    use ProphecyTrait;

    private string $directory;
    private ObjectProphecy $locks;
    private ObjectProphecy $overlay;
    private ObjectProphecy $bucket;
    private ObjectProphecy $redis;
    private ObjectProphecy $indexEnsurer;
    private ObjectProphecy $idFilter;
    private MaintenanceLock $lock;
    /** @var array<int, array{string, array<string, mixed>}> */
    private array $statements = [];

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/mphf-build-test-'.bin2hex(random_bytes(6));
        mkdir($this->directory);
        $this->lock = new MaintenanceLock(MaintenanceLockType::MPHF, 't');
        $this->locks = $this->prophesize(MaintenanceLockService::class);
        $this->locks->claim(MaintenanceLockType::MPHF)->willReturn($this->lock);
        $this->locks->isHeld(MaintenanceLockType::DATABASE)->willReturn(false);
        $this->locks->isStillOwned($this->lock)->willReturn(true);
        $this->locks->refresh($this->lock)->willReturn(true);
        $this->locks->release($this->lock)->willReturn(true);
        $this->overlay = $this->prophesize(ElementTypeOverlayService::class);
        $this->bucket = $this->prophesize(SystemBucketService::class);
        $this->bucket->ensureBucketExists();
        $this->bucket->list('mphf/')->willReturn([]);
        $this->redis = $this->prophesize(Client::class);
        $this->indexEnsurer = $this->prophesize(IndexEnsurer::class);
        $this->idFilter = $this->prophesize(ElementIdFilterService::class);
        $this->idFilter->isEnabled()->willReturn(false);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    private function fakeBuilder(bool $succeeds, bool $validOutput = true): string
    {
        $fixture = $this->directory.'/fixture';
        file_put_contents($fixture, $validOutput ? MphfFixture::file('payload') : MphfFixture::preface(7, str_repeat('0', 64)).'payload');
        $script = $this->directory.'/builder.sh';
        file_put_contents($script, $succeeds
            ? "#!/bin/sh\necho \"$@\" > ".$this->directory."/args\ncp ".$fixture." \"$5\"\n"
            : "#!/bin/sh\necho 'builder exploded' >&2\nexit 3\n");
        chmod($script, 0o755);

        return $script;
    }

    /**
     * @param array<string, string[]> $idsByType rows by exported type; the first element of each batch list is returned
     */
    private function create(string $builder, array $idsByType = ['Data' => ['a', 'b'], 'OWNS' => ['r']], int $batchSize = 10, int $retention = 7): MphfBuildService
    {
        $test = $this;
        $client = $this->prophesize(ClientInterface::class);
        $client->runStatement(Argument::any())->will(function (array $args) use ($test, $idsByType) {
            $test->statements[] = [$args[0]->getText(), $args[0]->getParameters()];
            $params = $args[0]->getParameters();
            if (str_contains($args[0]->getText(), 'count(')) {
                $summary = null;

                return new SummarizedResult($summary, [new CypherMap(['count' => 1000])]);
            }
            $type = (string) ($params['type'] ?? '');
            if ('' === $type) {
                // relation export has no type parameter, identified by its pattern
                $type = str_contains($args[0]->getText(), '`OWNS`') ? 'OWNS' : '';
            }
            $ids = array_values(array_filter($idsByType[$type] ?? [], static fn (string $id) => $id > $params['last']));
            $summary = null;

            return new SummarizedResult($summary, array_map(static fn (string $id) => new CypherMap(['id' => $id]), array_slice($ids, 0, $params['limit'])));
        });
        $entityManager = $this->prophesize(CypherEntityManager::class);
        $entityManager->getClient()->willReturn($client->reveal());
        $graph = $this->prophesize(GraphStructureService::class);
        $graph->loadGraphStructure();
        $graph->getNodeTypes()->willReturn(['Data']);
        $graph->getRelationTypes()->willReturn(['OWNS']);

        return new MphfBuildService(
            $entityManager->reveal(),
            $graph->reveal(),
            $this->indexEnsurer->reveal(),
            $this->locks->reveal(),
            $this->overlay->reveal(),
            $this->idFilter->reveal(),
            new MphfStorageService('memory', $this->directory.'/storage'),
            $this->bucket->reveal(),
            $this->redis->reveal(),
            new RedisKeyFactory(),
            new NullLogger(),
            0,
            0,
            $retention,
            $batchSize,
            $builder,
            'test',
        );
    }

    public function testSkipsWhenLockIsTaken(): void
    {
        $this->locks->claim(MaintenanceLockType::MPHF)->willReturn(null);
        $this->overlay->switchActive()->shouldNotBeCalled();

        $result = $this->create($this->fakeBuilder(true))->build();

        $this->assertTrue($result->isSkipped());
        $this->assertStringContainsString('lock:maintenance:mphf', (string) $result->getMessage());
    }

    public function testSkipsDuringDatabaseMaintenanceAndReleasesLock(): void
    {
        $this->locks->isHeld(MaintenanceLockType::DATABASE)->willReturn(true);
        $this->locks->release($this->lock)->shouldBeCalledOnce()->willReturn(true);

        $result = $this->create($this->fakeBuilder(true))->build();

        $this->assertTrue($result->isSkipped());
    }

    public function testSuccessfulBuildPublishesInOrder(): void
    {
        $order = [];
        $this->overlay->switchActive()->will(function () use (&$order) {
            $order[] = 'switch';

            return \App\Type\MphfOverlaySlot::B;
        });
        $this->bucket->upload(Argument::that(fn (string $key) => 1 === preg_match('#^mphf/\d{8}-\d{4}\.mphf$#', $key)), Argument::type('string'))->will(function () use (&$order) {
            $order[] = 'upload';
        })->shouldBeCalledOnce();
        $this->redis->set('mphf-version', Argument::that(fn (string $version) => 1 === preg_match('/^\d{8}-\d{4}\.mphf$/', $version)))->will(function () use (&$order) {
            $order[] = 'set-version';
        })->shouldBeCalledOnce();
        $this->overlay->clearInactive()->will(function () use (&$order) {
            $order[] = 'clear';
        })->shouldBeCalledOnce();
        $this->locks->release($this->lock)->shouldBeCalledOnce()->willReturn(true);
        $this->indexEnsurer->ensureWithLock(Argument::cetera())->shouldBeCalledOnce()->willReturn(null);
        $lines = [];

        $result = $this->create($this->fakeBuilder(true))->build(function (string $line) use (&$lines): void {
            $lines[] = $line;
        });

        $this->assertFalse($result->isSkipped());
        $this->assertSame(3, $result->getKeyCount());
        $this->assertSame(filesize($this->directory.'/fixture'), $result->getSizeInBytes());
        $this->assertSame(['switch', 'upload', 'set-version', 'clear'], $order);
        $this->assertNotEmpty($lines);
        $this->assertMatchesRegularExpression('/^\d{8}-\d{4}\.mphf$/', (string) $result->getVersion());
        $args = trim((string) file_get_contents($this->directory.'/args'));
        $this->assertMatchesRegularExpression('/^build --input \S+ --output \S+ --watermark-ms \d+ --generator Ember Nexus API test$/', $args);
    }

    public function testExportPagesByKeysetAndFiltersByLabel(): void
    {
        $this->bucket->upload(Argument::cetera());
        $this->redis->set(Argument::cetera());
        $this->overlay->switchActive()->willReturn(\App\Type\MphfOverlaySlot::B);
        $this->overlay->clearInactive();
        $this->indexEnsurer->ensureWithLock(Argument::cetera())->willReturn(null);

        $result = $this->create($this->fakeBuilder(true), ['Data' => ['a', 'b', 'c'], 'OWNS' => ['r']], batchSize: 2)->build();

        $this->assertSame(4, $result->getKeyCount());
        $nodeQueries = array_values(array_filter($this->statements, static fn (array $statement) => str_contains($statement[0], '`Data`')));
        $this->assertGreaterThanOrEqual(2, count($nodeQueries));
        $this->assertStringContainsString('labels(e)[0] = $type', $nodeQueries[0][0]);
        $this->assertStringContainsString('ORDER BY e.id LIMIT $limit', $nodeQueries[0][0]);
        $this->assertSame('', $nodeQueries[0][1]['last']);
        $this->assertSame('b', $nodeQueries[1][1]['last']);
    }

    public function testBuilderFailureThrowsAndReleasesLockWithoutPublishing(): void
    {
        $this->overlay->switchActive()->willReturn(\App\Type\MphfOverlaySlot::B);
        $this->indexEnsurer->ensureWithLock(Argument::cetera())->willReturn(null);
        $this->bucket->upload(Argument::cetera())->shouldNotBeCalled();
        $this->redis->set(Argument::cetera())->shouldNotBeCalled();
        $this->locks->release($this->lock)->shouldBeCalledOnce()->willReturn(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('builder exploded');
        $this->create($this->fakeBuilder(false))->build();
    }

    public function testCorruptBuilderOutputIsRejected(): void
    {
        $this->overlay->switchActive()->willReturn(\App\Type\MphfOverlaySlot::B);
        $this->indexEnsurer->ensureWithLock(Argument::cetera())->willReturn(null);
        $this->bucket->upload(Argument::cetera())->shouldNotBeCalled();

        $this->expectExceptionMessage('checksum');
        $this->create($this->fakeBuilder(true, false))->build();
    }

    public function testLostLockPreventsPublishing(): void
    {
        $this->overlay->switchActive()->willReturn(\App\Type\MphfOverlaySlot::B);
        $this->indexEnsurer->ensureWithLock(Argument::cetera())->willReturn(null);
        $this->locks->isStillOwned($this->lock)->willReturn(false);
        $this->bucket->upload(Argument::cetera())->shouldNotBeCalled();
        $this->redis->set(Argument::cetera())->shouldNotBeCalled();

        $this->expectExceptionMessage('was lost during the build');
        $this->create($this->fakeBuilder(true))->build();
    }

    public function testRetentionKeepsNewestAndDeletesRest(): void
    {
        $this->overlay->switchActive()->willReturn(\App\Type\MphfOverlaySlot::B);
        $this->overlay->clearInactive();
        $this->indexEnsurer->ensureWithLock(Argument::cetera())->willReturn(null);
        $this->bucket->upload(Argument::cetera());
        $this->redis->set(Argument::cetera());
        $current = gmdate('Ymd-Hi').'.mphf';
        $this->bucket->list('mphf/')->willReturn(['mphf/20200101-0000.mphf', 'mphf/20200102-0000.mphf', 'mphf/'.$current, 'mphf/notes.txt']);
        $this->bucket->delete('mphf/20200101-0000.mphf')->shouldBeCalledOnce();
        $this->bucket->delete('mphf/20200102-0000.mphf')->shouldNotBeCalled();

        $result = $this->create($this->fakeBuilder(true), retention: 2)->build();

        $this->assertSame(1, $result->getDeletedFileCount());
    }

    public function testIndexCreationBeingSkippedDoesNotStopTheBuild(): void
    {
        $this->overlay->switchActive()->willReturn(\App\Type\MphfOverlaySlot::B);
        $this->overlay->clearInactive();
        $this->bucket->upload(Argument::cetera());
        $this->redis->set(Argument::cetera());
        $this->indexEnsurer->ensureWithLock(Argument::cetera())->willReturn(null);

        $result = $this->create($this->fakeBuilder(true))->build();

        $this->assertFalse($result->isSkipped());
    }

    public function testElementIdFilterIsPreparedWithTheOverlayFilledWhileExportingAndCompletedLast(): void
    {
        $order = [];
        $this->idFilter->isEnabled()->willReturn(true);
        $this->overlay->switchActive()->will(function () use (&$order) {
            $order[] = 'switch';

            return \App\Type\MphfOverlaySlot::B;
        });
        // two counts of 1000 elements each, with a reserve of 20 %
        $this->idFilter->prepareBuild(2400)->will(function () use (&$order) {
            $order[] = 'prepare';

            return \App\Type\MphfOverlaySlot::A;
        })->shouldBeCalledOnce();
        $this->idFilter->addMany(\App\Type\MphfOverlaySlot::A, ['a', 'b'])->will(function () use (&$order): void {
            $order[] = 'add-nodes';
        })->shouldBeCalledOnce();
        $this->idFilter->addMany(\App\Type\MphfOverlaySlot::A, ['r'])->will(function () use (&$order): void {
            $order[] = 'add-relations';
        })->shouldBeCalledOnce();
        $this->idFilter->finishBuild(\App\Type\MphfOverlaySlot::A)->will(function () use (&$order): void {
            $order[] = 'finish';
        })->shouldBeCalledOnce();
        $this->overlay->clearInactive()->will(function () use (&$order): void {
            $order[] = 'clear';
        });
        $this->bucket->upload(Argument::cetera())->will(function () use (&$order): void {
            $order[] = 'upload';
        });
        $this->redis->set(Argument::cetera());
        $this->indexEnsurer->ensureWithLock(Argument::cetera())->willReturn(null);

        $this->create($this->fakeBuilder(true))->build();

        $this->assertSame(['switch', 'prepare', 'add-nodes', 'add-relations', 'upload', 'clear', 'finish'], $order);
    }

    public function testElementIdFilterIsFilledInTheBatchesOfTheExport(): void
    {
        $this->idFilter->isEnabled()->willReturn(true);
        $this->idFilter->prepareBuild(Argument::any())->willReturn(\App\Type\MphfOverlaySlot::B);
        $this->idFilter->addMany(\App\Type\MphfOverlaySlot::B, ['a', 'b'])->shouldBeCalledOnce();
        $this->idFilter->addMany(\App\Type\MphfOverlaySlot::B, ['c'])->shouldBeCalledOnce();
        $this->idFilter->addMany(\App\Type\MphfOverlaySlot::B, ['r'])->shouldBeCalledOnce();
        $this->idFilter->finishBuild(Argument::any());
        $this->overlay->switchActive()->willReturn(\App\Type\MphfOverlaySlot::A);
        $this->overlay->clearInactive();
        $this->bucket->upload(Argument::cetera());
        $this->redis->set(Argument::cetera());
        $this->indexEnsurer->ensureWithLock(Argument::cetera())->willReturn(null);

        $this->create($this->fakeBuilder(true), ['Data' => ['a', 'b', 'c'], 'OWNS' => ['r']], batchSize: 2)->build();
    }

    public function testRedisWhichCanNotCreateTheFilterDoesNotFailTheBuild(): void
    {
        $this->idFilter->isEnabled()->willReturn(true);
        $this->idFilter->prepareBuild(Argument::any())->willThrow(new RuntimeException("ERR unknown command 'BF.RESERVE'"));
        $this->idFilter->addMany(Argument::cetera())->shouldNotBeCalled();
        $this->idFilter->finishBuild(Argument::any())->shouldNotBeCalled();
        $this->overlay->switchActive()->willReturn(\App\Type\MphfOverlaySlot::B);
        $this->overlay->clearInactive();
        $this->bucket->upload(Argument::cetera());
        $this->redis->set(Argument::cetera());
        $this->indexEnsurer->ensureWithLock(Argument::cetera())->willReturn(null);
        $lines = [];

        $result = $this->create($this->fakeBuilder(true))->build(function (string $line) use (&$lines): void {
            $lines[] = $line;
        });

        $this->assertFalse($result->isSkipped());
        $this->assertStringContainsString('Element id filter skipped', implode("\n", $lines));
    }

    public function testDisabledElementIdFilterIsNeitherPreparedNorFilled(): void
    {
        $this->idFilter->prepareBuild(Argument::any())->shouldNotBeCalled();
        $this->idFilter->addMany(Argument::cetera())->shouldNotBeCalled();
        $this->idFilter->finishBuild(Argument::any())->shouldNotBeCalled();
        $this->overlay->switchActive()->willReturn(\App\Type\MphfOverlaySlot::B);
        $this->overlay->clearInactive();
        $this->bucket->upload(Argument::cetera());
        $this->redis->set(Argument::cetera());
        $this->indexEnsurer->ensureWithLock(Argument::cetera())->willReturn(null);

        $this->create($this->fakeBuilder(true))->build();
    }

    public function testElementIdFilterIsNotCompletedIfTheBuildFails(): void
    {
        $this->idFilter->isEnabled()->willReturn(true);
        $this->idFilter->prepareBuild(Argument::any())->willReturn(\App\Type\MphfOverlaySlot::B);
        $this->idFilter->addMany(Argument::cetera());
        $this->idFilter->finishBuild(Argument::any())->shouldNotBeCalled();
        $this->overlay->switchActive()->willReturn(\App\Type\MphfOverlaySlot::A);
        $this->indexEnsurer->ensureWithLock(Argument::cetera())->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->create($this->fakeBuilder(false))->build();
    }
}
