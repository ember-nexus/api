<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\SearchStep\EventListener;

use App\Antlr\CypherPathSubsetGrammar;
use App\EventSystem\SearchStep\Event\SearchStepEvent;
use App\EventSystem\SearchStep\EventListener\CypherPathSearchStepEventListener;
use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Type\SearchStepType;
use App\Type\Uuid;
use Laudis\Neo4j\Types\CypherList;
use Laudis\Neo4j\Types\CypherMap;
use Laudis\Neo4j\Types\Node;
use Laudis\Neo4j\Types\Path;
use Laudis\Neo4j\Types\UnboundRelationship;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use ReflectionMethod;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

#[Small]
#[CoversClass(CypherPathSearchStepEventListener::class)]
class CypherPathSearchStepEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function buildCypherPathSearchStepEventListener(
        ?CypherPathSubsetGrammar $cypherPathSubsetGrammar = null,
        ?CypherEntityManager $cypherEntityManager = null,
        ?AccessChecker $accessChecker = null,
        ?AuthProvider $authProvider = null,
        ?Stopwatch $stopwatch = null,
        ?Client400BadContentExceptionFactory $client400BadContentExceptionFactory = null,
        ?Server500LogicErrorExceptionFactory $server500LogicExceptionFactory = null,
    ): CypherPathSearchStepEventListener {
        $cypherPathSubsetGrammar = $cypherPathSubsetGrammar ?? self::prophesize(CypherPathSubsetGrammar::class)->reveal();
        $cypherEntityManager = $cypherEntityManager ?? self::prophesize(CypherEntityManager::class)->reveal();
        $accessChecker = $accessChecker ?? self::prophesize(AccessChecker::class)->reveal();
        $authProvider = $authProvider ?? self::prophesize(AuthProvider::class)->reveal();
        $stopwatch = $stopwatch ?? self::prophesize(Stopwatch::class)->reveal();
        $client400BadContentExceptionFactory = $client400BadContentExceptionFactory ?? self::prophesize(Client400BadContentExceptionFactory::class)->reveal();
        $server500LogicExceptionFactory = $server500LogicExceptionFactory ?? self::prophesize(Server500LogicErrorExceptionFactory::class)->reveal();

        return new CypherPathSearchStepEventListener(
            $cypherPathSubsetGrammar,
            $cypherEntityManager,
            $accessChecker,
            $authProvider,
            $stopwatch,
            $client400BadContentExceptionFactory,
            $server500LogicExceptionFactory
        );
    }

    public function testParseRawPathFromRowParsesRowCorrectly(): void
    {
        $cypherPathSearchStepEventListener = $this->buildCypherPathSearchStepEventListener();
        $method = new ReflectionMethod(CypherPathSearchStepEventListener::class, 'parseRawPathFromRow');

        $row = new CypherMap([
            'path' => new Path(
                new CypherList([
                    new Node(0, new CypherList([]), new CypherMap(['id' => '02IiNr0G8viOVKjb3rgIk6d1avGq8DSTDo3gH']), '0'),
                    new Node(1, new CypherList([]), new CypherMap(['id' => '02IiNr0G8viOVKjRq3mcc33Eok6ZQjl94VAvx']), '1'),
                ]),
                new CypherList([
                    new UnboundRelationship(0, 'type', new CypherMap(['id' => '02IiNr0G8viOVKjhS6CYaiwTWkyr9YBtOkkrg']), '0'),
                ]),
                new CypherList()
            ),
        ]);

        $result = $method->invokeArgs($cypherPathSearchStepEventListener, [$row]);

        $this->assertCount(2, $result['nodeIds']);
        $this->assertSame('02IiNr0G8viOVKjb3rgIk6d1avGq8DSTDo3gH', (string) $result['nodeIds'][0]);
        $this->assertSame('02IiNr0G8viOVKjRq3mcc33Eok6ZQjl94VAvx', (string) $result['nodeIds'][1]);
        $this->assertCount(1, $result['relationIds']);
        $this->assertSame('02IiNr0G8viOVKjhS6CYaiwTWkyr9YBtOkkrg', (string) $result['relationIds'][0]);
    }

    public function testParseRawPathFromRowParsesRowOfRewrittenQuery(): void
    {
        $listener = $this->buildCypherPathSearchStepEventListener();
        $method = new ReflectionMethod(CypherPathSearchStepEventListener::class, 'parseRawPathFromRow');

        $row = new CypherMap([
            'nodeIds' => new CypherList(['02IiNr0G8viOVKjb3rgIk6d1avGq8DSTDo3gH', '02IiNr0G8viOVKjRq3mcc33Eok6ZQjl94VAvx']),
            'relationIds' => new CypherList(['02IiNr0G8viOVKjhS6CYaiwTWkyr9YBtOkkrg']),
        ]);

        $result = $method->invokeArgs($listener, [$row]);

        $this->assertSame(['02IiNr0G8viOVKjb3rgIk6d1avGq8DSTDo3gH', '02IiNr0G8viOVKjRq3mcc33Eok6ZQjl94VAvx'], array_map(strval(...), $result['nodeIds']));
        $this->assertSame(['02IiNr0G8viOVKjhS6CYaiwTWkyr9YBtOkkrg'], array_map(strval(...), $result['relationIds']));
    }

    public function testParseRawPathFromRowOfRewrittenQueryChecksTheNumberOfRelations(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->willReturn('https://mock.dev/123');
        $listener = $this->buildCypherPathSearchStepEventListener(
            client400BadContentExceptionFactory: new Client400BadContentExceptionFactory($urlGenerator->reveal())
        );
        $method = new ReflectionMethod(CypherPathSearchStepEventListener::class, 'parseRawPathFromRow');

        $this->expectException(Client400BadContentException::class);
        $method->invokeArgs($listener, [new CypherMap([
            'nodeIds' => new CypherList(['02IiNr0G8viOVKjb3rgIk6d1avGq8DSTDo3gH']),
            'relationIds' => new CypherList(['02IiNr0G8viOVKjhS6CYaiwTWkyr9YBtOkkrg']),
        ])]);
    }

    public function testParseRawPathFromRowThrowsErrorWhenNumberOfElementsIsUnexpected(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);

        $cypherPathSearchStepEventListener = $this->buildCypherPathSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(CypherPathSearchStepEventListener::class, 'parseRawPathFromRow');

        $row = new CypherMap([
            'path' => new Path(
                new CypherList([
                    new Node(0, new CypherList([]), new CypherMap(['id' => '02IiNr0G8viOVKjb3rgIk6d1avGq8DSTDo3gH']), '0'),
                ]),
                new CypherList([
                    new UnboundRelationship(0, 'type', new CypherMap(['id' => '02IiNr0G8viOVKjhS6CYaiwTWkyr9YBtOkkrg']), '0'),
                ]),
                new CypherList()
            ),
        ]);

        try {
            $method->invokeArgs($cypherPathSearchStepEventListener, [$row]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame('Result contains path with unexpected number of relations. For 1 number of nodes expected 0 number of relations, got 1.', $exception->getDetail());
        }
    }

    public function testGetElementIdsFromPathsOperatesCorrectly(): void
    {
        $cypherPathSearchStepEventListener = $this->buildCypherPathSearchStepEventListener();
        $method = new ReflectionMethod(CypherPathSearchStepEventListener::class, 'getElementIdsFromPaths');

        $elementIdA = Uuid::fromString('02IiNr0G8viOVKkhRtkouQlLCT96e1EytUwAN');
        $elementIdB = Uuid::fromString('02IiNr0G8viOVKkObnR4Ty6lD1EcqNse4n7lv');
        $elementIdC = Uuid::fromString('02IiNr0G8viOVKjwp4P42npyMiQxhoS94ydJA');
        $elementIdD = Uuid::fromString('02IiNr0G8viOVKl8mODV5zK1vHTn9ucMCYFQ7');
        $elementIdE = Uuid::fromString('02IiNr0G8viOVKku18MT9stADWuP3fwnXT3sH');

        $paths = [
            [
                'nodeIds' => [$elementIdA, $elementIdB],
                'relationIds' => [$elementIdC],
            ],
            [
                'nodeIds' => [$elementIdD],
                'relationIds' => [],
            ],
            [
                'nodeIds' => [$elementIdA, $elementIdD],
                'relationIds' => [$elementIdE],
            ],
        ];

        $result = $method->invokeArgs($cypherPathSearchStepEventListener, [$paths]);

        $this->assertCount(5, $result);
        $this->assertSame('02IiNr0G8viOVKkhRtkouQlLCT96e1EytUwAN', (string) $result[0]);
        $this->assertSame('02IiNr0G8viOVKkObnR4Ty6lD1EcqNse4n7lv', (string) $result[1]);
        $this->assertSame('02IiNr0G8viOVKl8mODV5zK1vHTn9ucMCYFQ7', (string) $result[2]);
        $this->assertSame('02IiNr0G8viOVKjwp4P42npyMiQxhoS94ydJA', (string) $result[3]);
        $this->assertSame('02IiNr0G8viOVKku18MT9stADWuP3fwnXT3sH', (string) $result[4]);
    }

    public function testFilterPathsToAccessibleOnlyOperatesCorrectly(): void
    {
        $userId = Uuid::fromString('02IiNr0G8viOVKjo9DHasDKs1LXdANErG15iL');

        $elementIdA = Uuid::fromString('02IiNr0G8viOVKjnXUB2dyBe4uq3JswDduJ2S');
        $elementIdB = Uuid::fromString('02IiNr0G8viOVKjvKY1S99oEuAPIX8xD3Yzfb');
        $elementIdC = Uuid::fromString('02IiNr0G8viOVKjPFi9uvWHbOm7ELF06UInor');
        $elementIdD = Uuid::fromString('02IiNr0G8viOVKjcdyttax4LieWnpmzch0Apz');
        $elementIdE = Uuid::fromString('02IiNr0G8viOVKlDlrFRpx2FVAG1rGyLfE9qN');
        $elementIdF = Uuid::fromString('02IiNr0G8viOVKkfFxcCxBFcKUChLvx0wLg14');

        $stopwatch = $this->prophesize(Stopwatch::class);
        $stopwatch
            ->start(Argument::is('cypher-path-subset:checkUserAccessToMultipleElements'))
            ->shouldBeCalledOnce()
            ->willReturn($this->prophesize(StopwatchEvent::class)->reveal());
        $stopwatch
            ->stop(Argument::is('cypher-path-subset:checkUserAccessToMultipleElements'))
            ->shouldBeCalledOnce()
            ->willReturn($this->prophesize(StopwatchEvent::class)->reveal());

        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider
            ->getUserId()
            ->shouldBeCalledOnce()
            ->willReturn($userId);

        $accessChecker = $this->prophesize(AccessChecker::class);
        $accessChecker
            ->checkUserAccessToMultipleElements(Argument::is($userId), Argument::any())
            ->shouldBeCalledOnce()
            ->willReturn([
                $elementIdA,
                $elementIdB,
                $elementIdC,
                $elementIdD,
            ]);

        $cypherPathSearchStepEventListener = $this->buildCypherPathSearchStepEventListener(
            accessChecker: $accessChecker->reveal(),
            authProvider: $authProvider->reveal(),
            stopwatch: $stopwatch->reveal()
        );
        $method = new ReflectionMethod(CypherPathSearchStepEventListener::class, 'filterPathsToAccessibleOnly');

        $paths = [
            [
                'nodeIds' => [$elementIdA, $elementIdB],
                'relationIds' => [$elementIdC],
            ],
            [
                'nodeIds' => [$elementIdF],
                'relationIds' => [],
            ],
            [
                'nodeIds' => [$elementIdD],
                'relationIds' => [],
            ],
            [
                'nodeIds' => [$elementIdA, $elementIdD],
                'relationIds' => [$elementIdE],
            ],
        ];

        $result = $method->invokeArgs($cypherPathSearchStepEventListener, [$paths]);

        $this->assertCount(2, $result);
        $this->assertSame($paths[0], $result[0]);
        $this->assertSame($paths[2], $result[1]);
    }

    public function testGetQueryFromEventAcceptsStringQuery(): void
    {
        $cypherPathSearchStepEventListener = $this->buildCypherPathSearchStepEventListener();
        $method = new ReflectionMethod(CypherPathSearchStepEventListener::class, 'getQueryFromEvent');

        $event = new SearchStepEvent(
            SearchStepType::CYPHER_PATH_SUBSET,
            'some query',
            []
        );

        $result = $method->invokeArgs($cypherPathSearchStepEventListener, [$event]);
        $this->assertSame('some query', $result);
    }

    public function testGetQueryFromEventFailsOnNullQuery(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);

        $cypherPathSearchStepEventListener = $this->buildCypherPathSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(CypherPathSearchStepEventListener::class, 'getQueryFromEvent');

        $event = new SearchStepEvent(
            SearchStepType::CYPHER_PATH_SUBSET,
            null,
            []
        );

        try {
            $method->invokeArgs($cypherPathSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'query' to be string, got null.", $exception->getDetail());
        }
    }

    public function testGetQueryFromEventFailsOnArrayQuery(): void
    {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is(['code' => '400', 'name' => 'bad-content']),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);

        $cypherPathSearchStepEventListener = $this->buildCypherPathSearchStepEventListener(
            client400BadContentExceptionFactory: $client400BadContentExceptionFactory
        );

        $method = new ReflectionMethod(CypherPathSearchStepEventListener::class, 'getQueryFromEvent');

        $event = new SearchStepEvent(
            SearchStepType::CYPHER_PATH_SUBSET,
            [
                'some' => 'query',
            ],
            []
        );

        try {
            $method->invokeArgs($cypherPathSearchStepEventListener, [$event]);
            $this->fail('Expected exception to be thrown.');
        } catch (Client400BadContentException $exception) {
            $this->assertSame("Endpoint expects property 'query' to be string, got array with one element.", $exception->getDetail());
        }
    }

    public function testUserQueriesAreAbortedAfterThirtySeconds(): void
    {
        $this->assertSame(30.0, CypherPathSearchStepEventListener::QUERY_TIMEOUT_IN_SECONDS);
    }
}
