<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Contract\MphfInterface;
use App\Service\ElementTypeService;
use App\Service\Mphf\ElementIdFilterService;
use App\Service\Mphf\ElementTypeOverlayService;
use App\Service\Mphf\MphfVersionSynchronizer;
use App\Type\ElementKind;
use App\Type\Uuid;
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
#[CoversClass(ElementTypeService::class)]
class ElementTypeServiceTest extends TestCase
{
    use ProphecyTrait;

    private ObjectProphecy $overlay;
    private ObjectProphecy $mphf;
    private ObjectProphecy $synchronizer;
    private ObjectProphecy $idFilter;
    private ObjectProphecy $client;
    /** @var string[] */
    private array $queries = [];
    private Uuid $id;

    protected function setUp(): void
    {
        $this->id = Uuid::generate();
        $this->overlay = $this->prophesize(ElementTypeOverlayService::class);
        $this->overlay->lookup(Argument::any())->willReturn(null);
        $this->mphf = $this->prophesize(MphfInterface::class);
        $this->mphf->lookup(Argument::any())->willReturn('');
        $this->mphf->version()->willReturn('');
        $this->idFilter = $this->prophesize(ElementIdFilterService::class);
        $this->idFilter->mightExist(Argument::any())->willReturn(null);
        $this->synchronizer = $this->prophesize(MphfVersionSynchronizer::class);
        $this->synchronizer->ensureSynchronized();
        $this->synchronizer->isLoadedVersionCurrent()->willReturn(true);
        $this->client = $this->prophesize(ClientInterface::class);
    }

    /**
     * @param array<int, array<string, mixed>>|null $rows rows returned for every query, null for none
     */
    private function answerQueries(?array $rows): void
    {
        $test = $this;
        $this->client->runStatement(Argument::any())->will(function (array $args) use ($test, $rows) {
            $test->queries[] = $args[0]->getText();
            $summary = null;

            return new SummarizedResult($summary, array_map(static fn (array $row) => new CypherMap($row), $rows ?? []));
        });
    }

    private function create(bool $strict = true): ElementTypeService
    {
        $entityManager = $this->prophesize(CypherEntityManager::class);
        $entityManager->getClient()->willReturn($this->client->reveal());

        return new ElementTypeService(
            $this->overlay->reveal(),
            $this->mphf->reveal(),
            $this->synchronizer->reveal(),
            $this->idFilter->reveal(),
            $entityManager->reveal(),
            new NullLogger(),
            $strict,
        );
    }

    public function testOverlayWinsAndIsNotVerified(): void
    {
        $this->overlay->lookup($this->id->toString())->willReturn('r:OWNS');
        $this->mphf->lookup(Argument::any())->shouldNotBeCalled();
        $this->answerQueries([]);

        $resolved = $this->create(false)->resolve($this->id);

        $this->assertNotNull($resolved);
        $this->assertTrue($resolved->isRelation());
        $this->assertSame('OWNS', $resolved->getType());
        $this->assertSame([], $this->queries);
    }

    public function testStrictModeTrustsTheCandidate(): void
    {
        $this->mphf->lookup($this->id->toString())->willReturn('n:Data');
        $this->mphf->version()->willReturn('v');
        $this->answerQueries([]);
        $service = $this->create();

        $this->assertSame('Data', $service->resolveNodeLabel($this->id));
        $this->assertNull($service->resolveRelationType($this->id));
        $this->assertSame([], $this->queries);
    }

    public function testNonStrictModeVerifiesTheCandidate(): void
    {
        $this->mphf->lookup($this->id->toString())->willReturn('n:Data');
        $this->mphf->version()->willReturn('v');
        $this->answerQueries([['found' => 1]]);

        $resolved = $this->create(false)->resolve($this->id);

        $this->assertSame('Data', $resolved?->getType());
        $this->assertCount(1, $this->queries);
        $this->assertStringContainsString('MATCH (e:`Data` {id: $id})', $this->queries[0]);
    }

    public function testElementExistsRunsOneTypedQueryWithTheCandidate(): void
    {
        $this->mphf->lookup($this->id->toString())->willReturn('n:Data');
        $this->mphf->version()->willReturn('v');
        $this->answerQueries([['found' => 1]]);

        $this->assertTrue($this->create()->elementExists($this->id));
        $this->assertCount(1, $this->queries);
        $this->assertStringContainsString('MATCH (e:`Data` {id: $id}) RETURN 1 AS found LIMIT 1', $this->queries[0]);
    }

    public function testElementExistsIsFalseWithoutAnyLookupIfTheIdFilterDoesNotKnowTheId(): void
    {
        $this->idFilter->mightExist($this->id->toString())->willReturn(false);
        $this->overlay->lookup(Argument::any())->shouldNotBeCalled();
        $this->mphf->lookup(Argument::any())->shouldNotBeCalled();
        $this->answerQueries([['found' => 1]]);

        $this->assertFalse($this->create()->elementExists($this->id));
        $this->assertCount(0, $this->queries);
    }

    public function testElementExistsStillAsksTheDatabaseIfTheIdFilterSaysMaybe(): void
    {
        $this->idFilter->mightExist($this->id->toString())->willReturn(true);
        $this->mphf->lookup($this->id->toString())->willReturn('n:Data');
        $this->mphf->version()->willReturn('v');
        // a false positive of the filter: the database decides
        $this->answerQueries([]);

        $this->assertFalse($this->create()->elementExists($this->id));
        $this->assertCount(1, $this->queries);
    }

    public function testElementExistsStillAsksTheDatabaseIfTheIdFilterCanNotBeUsed(): void
    {
        $this->idFilter->mightExist($this->id->toString())->willReturn(null);
        $this->mphf->lookup($this->id->toString())->willReturn('n:Data');
        $this->mphf->version()->willReturn('v');
        $this->answerQueries([['found' => 1]]);

        $this->assertTrue($this->create()->elementExists($this->id));
        $this->assertCount(1, $this->queries);
    }

    public function testElementExistsIsFalseIfTheCandidateIsWrong(): void
    {
        // the table answers with a candidate even for ids which it does not know
        $this->mphf->lookup($this->id->toString())->willReturn('n:Data');
        $this->mphf->version()->willReturn('v');
        $this->answerQueries([]);

        $this->assertFalse($this->create()->elementExists($this->id));
        $this->assertCount(1, $this->queries);
    }

    public function testElementExistsChecksOverlayTypesAgainstTheDatabaseToo(): void
    {
        // the overlay is not cleaned up when an element is deleted
        $this->overlay->lookup($this->id->toString())->willReturn('r:OWNS');
        $this->answerQueries([]);

        $this->assertFalse($this->create()->elementExists($this->id));
        $this->assertStringContainsString('()-[e:`OWNS` {id: $id}]->()', $this->queries[0]);
    }

    public function testElementExistsWithoutCandidateRunsNoQuery(): void
    {
        $this->mphf->version()->willReturn('v');
        $this->answerQueries([['found' => 1]]);

        $this->assertFalse($this->create()->elementExists($this->id));
        $this->assertSame([], $this->queries);
    }

    public function testVerifiesRelationCandidatesWithRelationPattern(): void
    {
        $this->mphf->lookup(Argument::any())->willReturn('r:OWNS');
        $this->mphf->version()->willReturn('v');
        $this->answerQueries([['found' => 1]]);

        $this->create(false)->resolve($this->id);

        $this->assertStringContainsString('()-[e:`OWNS` {id: $id}]->()', $this->queries[0]);
    }

    public function testFailedVerificationFallsBackToUntypedQuery(): void
    {
        $this->mphf->lookup(Argument::any())->willReturn('n:Data');
        $this->mphf->version()->willReturn('v');
        $this->synchronizer->isLoadedVersionCurrent()->willReturn(false);
        $test = $this;
        $this->client->runStatement(Argument::any())->will(function (array $args) use ($test) {
            $test->queries[] = $args[0]->getText();
            $summary = null;
            $rows = 1 === count($test->queries) ? [] : [new CypherMap(['kind' => 'node', 'type' => 'Group'])];

            return new SummarizedResult($summary, $rows);
        });

        $resolved = $this->create()->resolve($this->id);

        $this->assertSame('Group', $resolved?->getType());
        $this->assertCount(2, $this->queries);
        $this->assertStringContainsString('UNION', $this->queries[1]);
    }

    public function testStrictModeWithoutCandidateMeansNotFound(): void
    {
        $this->mphf->version()->willReturn('v');
        $this->answerQueries([]);

        $this->assertNull($this->create()->resolve($this->id));
        $this->assertSame([], $this->queries);
    }

    public function testStaleTableWithoutCandidateUsesUntypedQuery(): void
    {
        $this->mphf->version()->willReturn('v');
        $this->synchronizer->isLoadedVersionCurrent()->willReturn(false);
        $this->answerQueries([['kind' => 'relation', 'type' => 'OWNS']]);

        $resolved = $this->create()->resolve($this->id);

        $this->assertTrue($resolved?->isRelation());
        $this->assertCount(1, $this->queries);
    }

    public function testWithoutTableTheUntypedQueryIsUsed(): void
    {
        $this->answerQueries([['kind' => 'node', 'type' => 'Data']]);

        $resolved = $this->create()->resolve($this->id);

        $this->assertSame(ElementKind::NODE, $resolved?->getKind());
        $this->assertStringContainsString('UNION', $this->queries[0]);
    }

    public function testUntypedQueryWithoutResultAndInvalidType(): void
    {
        $this->answerQueries([]);
        $this->assertNull($this->create()->resolve($this->id));

        $this->answerQueries([['kind' => 'node', 'type' => null]]);
        $this->assertNull($this->create()->resolve(Uuid::generate()));
    }

    public function testPrefixDecidesTheKindOfANameUsedAsLabelAndRelationType(): void
    {
        $this->overlay->lookup($this->id->toString())->willReturn('n:Both');
        $otherId = Uuid::generate();
        $this->overlay->lookup($otherId->toString())->willReturn('r:Both');
        $this->answerQueries([]);
        $service = $this->create();

        $this->assertTrue($service->resolve($this->id)?->isNode());
        $this->assertTrue($service->resolve($otherId)?->isRelation());
        $this->assertSame([], $this->queries);
    }

    public function testUndecodableCandidateIsNotFoundInStrictMode(): void
    {
        $this->mphf->lookup(Argument::any())->willReturn('Brand');
        $this->mphf->version()->willReturn('v');
        $this->answerQueries([]);

        $this->assertNull($this->create()->resolve($this->id));
        $this->assertSame([], $this->queries);
    }

    public function testUndecodableCandidateFallsBackToUntypedQueryWhenVerifying(): void
    {
        $this->mphf->lookup(Argument::any())->willReturn('Brand');
        $this->mphf->version()->willReturn('v');
        $this->answerQueries([['kind' => 'node', 'type' => 'Data']]);

        $this->assertSame('Data', $this->create(false)->resolve($this->id)?->getType());
        $this->assertCount(1, $this->queries);
        $this->assertStringContainsString('UNION', $this->queries[0]);
    }

    public function testUnknownOverlayTypeFallsBackToUntypedQuery(): void
    {
        $this->overlay->lookup(Argument::any())->willReturn('Brand');
        $this->answerQueries([['kind' => 'node', 'type' => 'Data']]);

        $this->assertSame('Data', $this->create()->resolve($this->id)?->getType());
    }

    public function testOverlayFailureIsTreatedAsMiss(): void
    {
        $this->overlay->lookup(Argument::any())->willThrow(new RuntimeException('redis down'));
        $this->answerQueries([['kind' => 'node', 'type' => 'Data']]);

        $this->assertSame('Data', $this->create()->resolve($this->id)?->getType());
    }

    public function testResultsAreMemoisedUntilForgottenOrReset(): void
    {
        $this->answerQueries([['kind' => 'node', 'type' => 'Data']]);
        $service = $this->create();

        $service->resolve($this->id);
        $service->resolve($this->id);
        $this->assertCount(1, $this->queries);

        $service->forget($this->id);
        $service->resolve($this->id);
        $this->assertCount(2, $this->queries);

        $service->reset();
        $service->resolve($this->id);
        $this->assertCount(3, $this->queries);
    }

    public function testNotFoundIsMemoisedToo(): void
    {
        $this->answerQueries([]);
        $service = $this->create();

        $service->resolve($this->id);
        $service->resolve($this->id);

        $this->assertCount(1, $this->queries);
    }

    public function testResolveManyAsksTheOverlayOnceAndFallsBackToTheTablePerId(): void
    {
        $other = Uuid::generate();
        $this->overlay->lookup(Argument::any())->shouldNotBeCalled();
        $this->overlay->lookupMany([$this->id->toString(), $other->toString()])->shouldBeCalledOnce()->willReturn([$this->id->toString() => 'n:Data']);
        $this->mphf->lookup($other->toString())->willReturn('r:OWNS');
        $this->mphf->version()->willReturn('v1');
        $this->answerQueries([['found' => 1]]);

        $service = $this->create(false);
        $resolved = $service->resolveMany([$this->id, $other]);

        $this->assertSame('Data', $resolved[$this->id->toString()]?->getType());
        $this->assertTrue($resolved[$this->id->toString()]->isNode());
        $this->assertSame('OWNS', $resolved[$other->toString()]?->getType());
        $this->assertTrue($resolved[$other->toString()]->isRelation());
        // memoized: no further overlay access
        $this->assertSame('Data', $service->resolve($this->id)?->getType());
        $this->assertSame([], array_keys($service->resolveMany([])));
    }

    public function testResolveManyReturnsNullForUnknownIdsAndSurvivesRedisFailure(): void
    {
        $this->overlay->lookupMany(Argument::any())->willThrow(new RuntimeException('redis down'));
        $this->mphf->version()->willReturn('v1');
        $this->mphf->lookup(Argument::any())->willReturn('');

        $resolved = $this->create()->resolveMany([$this->id]);

        $this->assertSame([$this->id->toString() => null], $resolved);
    }
}
