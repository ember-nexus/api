<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\SearchStep\EventListener;

use App\Antlr\CypherPathSubsetGrammar;
use App\EventSystem\SearchStep\Event\SearchStepEvent;
use App\EventSystem\SearchStep\EventListener\CypherPathSearchStepEventListener;
use App\Exception\Client400BadContentException;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Type\SearchStepType;
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
use Ramsey\Uuid\Uuid;
use ReflectionMethod;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
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
        ?Server500LogicExceptionFactory $server500LogicExceptionFactory = null,
    ): CypherPathSearchStepEventListener {
        $cypherPathSubsetGrammar = $cypherPathSubsetGrammar ?? self::prophesize(CypherPathSubsetGrammar::class)->reveal();
        $cypherEntityManager = $cypherEntityManager ?? self::prophesize(CypherEntityManager::class)->reveal();
        $accessChecker = $accessChecker ?? self::prophesize(AccessChecker::class)->reveal();
        $authProvider = $authProvider ?? self::prophesize(AuthProvider::class)->reveal();
        $stopwatch = $stopwatch ?? self::prophesize(Stopwatch::class)->reveal();
        $client400BadContentExceptionFactory = $client400BadContentExceptionFactory ?? self::prophesize(Client400BadContentExceptionFactory::class)->reveal();
        $server500LogicExceptionFactory = $server500LogicExceptionFactory ?? self::prophesize(Server500LogicExceptionFactory::class)->reveal();

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
                    new Node(0, new CypherList([]), new CypherMap(['id' => '209b792a-4040-44f7-a635-37a933d5c735']), '0'),
                    new Node(1, new CypherList([]), new CypherMap(['id' => '0da46bbc-bb83-4ca8-a5f0-2e196bda3a92']), '1'),
                ]),
                new CypherList([
                    new UnboundRelationship(0, 'type', new CypherMap(['id' => '2db76287-43dd-4162-b961-36cf2c840a3e']), '0'),
                ]),
                new CypherList()
            ),
        ]);

        $result = $method->invokeArgs($cypherPathSearchStepEventListener, [$row]);

        $this->assertCount(2, $result['nodeIds']);
        $this->assertSame('209b792a-4040-44f7-a635-37a933d5c735', (string) $result['nodeIds'][0]);
        $this->assertSame('0da46bbc-bb83-4ca8-a5f0-2e196bda3a92', (string) $result['nodeIds'][1]);
        $this->assertCount(1, $result['relationIds']);
        $this->assertSame('2db76287-43dd-4162-b961-36cf2c840a3e', (string) $result['relationIds'][0]);
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
                    new Node(0, new CypherList([]), new CypherMap(['id' => '209b792a-4040-44f7-a635-37a933d5c735']), '0'),
                ]),
                new CypherList([
                    new UnboundRelationship(0, 'type', new CypherMap(['id' => '2db76287-43dd-4162-b961-36cf2c840a3e']), '0'),
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

        $elementIdA = Uuid::fromString('ad0c2924-0a22-4225-902f-b00cd6ad6e05');
        $elementIdB = Uuid::fromString('86534249-04d0-4b32-acea-7b47f7c6666d');
        $elementIdC = Uuid::fromString('4d4e1df5-a25d-47f2-bba7-a3fa9046f4fa');
        $elementIdD = Uuid::fromString('e52abeae-b6c1-4087-b5ff-282d865ff3cb');
        $elementIdE = Uuid::fromString('c6d0bae7-f9ac-43e7-8d05-f77239f1490c');

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
        $this->assertSame('ad0c2924-0a22-4225-902f-b00cd6ad6e05', (string) $result[0]);
        $this->assertSame('86534249-04d0-4b32-acea-7b47f7c6666d', (string) $result[1]);
        $this->assertSame('e52abeae-b6c1-4087-b5ff-282d865ff3cb', (string) $result[2]);
        $this->assertSame('4d4e1df5-a25d-47f2-bba7-a3fa9046f4fa', (string) $result[3]);
        $this->assertSame('c6d0bae7-f9ac-43e7-8d05-f77239f1490c', (string) $result[4]);
    }

    public function testFilterPathsToAccessibleOnlyOperatesCorrectly(): void
    {
        $userId = Uuid::fromString('3b74b11f-b53b-445e-be21-916f3504ac9c');

        $elementIdA = Uuid::fromString('3a365022-6b3f-40d1-84dd-528a93e70a85');
        $elementIdB = Uuid::fromString('4a35e57f-5296-41c2-88f4-9588ba9ac1e5');
        $elementIdC = Uuid::fromString('085a6162-5eda-4053-b45c-3bc0deae6ea0');
        $elementIdD = Uuid::fromString('23daa353-34fd-40d9-af5f-5e287a5674e3');
        $elementIdE = Uuid::fromString('ef6b6103-17f3-48cb-9e59-0be054a9ee4d');
        $elementIdF = Uuid::fromString('a881e529-d9bc-49cf-b579-89f6eb38ed68');

        $event = new SearchStepEvent(
            SearchStepType::CYPHER_PATH_SUBSET,
            'some query',
            []
        );

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

        $result = $method->invokeArgs($cypherPathSearchStepEventListener, [$event, $paths]);

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
}
