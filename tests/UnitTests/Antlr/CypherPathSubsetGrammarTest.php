<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Antlr;

use App\Antlr\AntlrSyntaxErrorListener;
use App\Antlr\AntlrSyntaxErrorListenerFactory;
use App\Antlr\CypherPathSubsetGrammar;
use App\Antlr\SingleReturnPathListener;
use App\Factory\Exception\Client400BadContentExceptionFactory;
use App\Factory\Exception\Client400BadGrammarExceptionFactory;
use App\Factory\Type\RedisKeyFactory;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

#[Small]
#[CoversClass(AntlrSyntaxErrorListener::class)]
#[CoversClass(AntlrSyntaxErrorListenerFactory::class)]
#[CoversClass(CypherPathSubsetGrammar::class)]
#[CoversClass(SingleReturnPathListener::class)]
class CypherPathSubsetGrammarTest extends TestCase
{
    use ProphecyTrait;

    private function buildCypherPathSubsetGrammar(
        bool $willThrowThrowable = false,
        ?RedisClient $redisClient = null,
    ): CypherPathSubsetGrammar {
        /** @var ObjectProphecy<UrlGeneratorInterface> $urlGenerator */
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::any(),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->willReturn('https://mock.dev/123');
        $urlGenerator = $urlGenerator->reveal();

        $client400BadGrammarExceptionFactory = new Client400BadGrammarExceptionFactory($urlGenerator);
        $client400BadContentExceptionFactory = new Client400BadContentExceptionFactory($urlGenerator);
        $antlrSyntaxErrorListenerFactory = new AntlrSyntaxErrorListenerFactory($client400BadGrammarExceptionFactory);

        $redisClient = $redisClient ?? $this->prophesize(RedisClient::class)->reveal();

        $stopwatch = $this->prophesize(Stopwatch::class);
        $stopwatch
            ->start(Argument::is('CypherPathSubsetGrammar::validateQuery'))
            ->shouldBeCalledOnce()
            ->willReturn($this->prophesize(StopwatchEvent::class)->reveal());
        $stopwatch
            ->stop(Argument::is('CypherPathSubsetGrammar::validateQuery'))
            ->shouldBeCalledTimes($willThrowThrowable ? 0 : 1)
            ->willReturn($this->prophesize(StopwatchEvent::class)->reveal());

        $cypherPathSubsetGrammar = new CypherPathSubsetGrammar(
            $antlrSyntaxErrorListenerFactory,
            $stopwatch->reveal(),
            $redisClient,
            new RedisKeyFactory(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $client400BadContentExceptionFactory
        );

        return $cypherPathSubsetGrammar;
    }

    public static function validQueryProvider(): array
    {
        return [
            ['MATCH path = ((:Type)) RETURN path'],
            ['MATCH path = ((:Type)) RETURN path;'],
            ['MATCH path = ((:Type)) RETURN `path`'],
            ['MATCH path = ((:Type)) RETURN `path`;'],
            ['MATCH path = ((:Type)) RETURN path LIMIT 10'],
            ['MATCH path = ((:Type)) RETURN `path` LIMIT 10;'],
            ['MATCH path = ((:Type)) RETURN DISTINCT path SKIP 5 LIMIT 10'],
            ['MATCH path = ((:Type)) RETURN DISTINCT `path` SKIP 5 LIMIT 10;'],
            ['MATCH path = ((n:Type)) WHERE n.id = "02IiNr0G8viOVKjLCfR4NIEdgVrLpaxVcZ7BI" RETURN path'],
            ['MATCH path=((:Plant)-[:HAS_TAG]->(:Tag {name: $color})) RETURN path'],
            ['UNWIND $stepResults[0].elements AS el MATCH path=((plant:Plant)-[:IS_MEMBER_OF*..]->(:Taxon)) WHERE plant.id = el.id RETURN path'],
            ['MATCH path=((:Plant)-[:IS_MEMBER_OF*..]->(t:Taxon)) WHERE t.name IN $taxonNames RETURN path LIMIT 5'],
            ['MATCH path=((plant:Plant WHERE plant.name = "Passionflower")) RETURN path'],
            ['MATCH path=(n:Data) WHERE n.hash = "x" RETURN path'],
            ['MATCH path=(n:Data|User) WHERE n.hash = "x" RETURN path'],
            ['MATCH path=(n:(Data&User)) WHERE n.hash = "x" RETURN path'],
            ['MATCH path=(n IS Data) WHERE n.hash = "x" RETURN path'],
            ['MATCH path=(n:Data {hash: "x"}) RETURN path'],
            ['MATCH path=(n:Data)-[r:HAS]->(:Data) WHERE r.hash = "x" RETURN path'],
            ['MATCH path=(n:Data)-[:HAS {hash: "x"}]->(:Data) RETURN path'],
            ['MATCH path=(n:Data)-[:HAS]->(m:Data) WHERE n.hash = m.hash RETURN path'],
            ['MATCH path=(n:Data)-[:HAS]->(n) WHERE n.hash = "x" RETURN path'],
        ];
    }

    #[DataProvider('validQueryProvider')]
    public function testValidQueriesDoNotThrowException(string $query): void
    {
        $cypherPathSubsetGrammar = $this->buildCypherPathSubsetGrammar();
        $result = $cypherPathSubsetGrammar->validateQuery($query);
        $this->assertTrue($result);
    }

    public static function invalidQueryProvider(): array
    {
        return [
            [
                'MATCH (n) SET n.property = "some value" RETURN n',
                'Syntax exception at line 1 position 10: missing RETURN at \'SET\'',
            ],
            [
                'CREATE (n) RETURN n;',
                'Syntax exception at line 1 position 0: mismatched input \'CREATE\' expecting {MATCH, UNWIND}',
            ],
            [
                'MERGE (n {property: "123"}) RETURN n',
                'Syntax exception at line 1 position 0: mismatched input \'MERGE\' expecting {MATCH, UNWIND}',
            ],
            [
                'MATCH path = ((:Type)) RETURN path RETURN path',
                'Syntax exception at line 1 position 35: mismatched input \'RETURN\' expecting {<EOF>, \';\'}',
            ],
            [
                'MATCH (n:Type) RETURN n',
                'Path query is not allowed to return variables other than \'path\', use \'RETURN path\'.',
            ],
            [
                'MATCH (n:Type) RETURN `n`',
                'Path query is not allowed to return variables other than \'path\', use \'RETURN path\'.',
            ],
            [
                'MATCH path = ((n:Type)) RETURN path, n',
                'Syntax exception at line 1 position 35: mismatched input \',\' expecting {<EOF>, \';\'}',
            ],
            [
                'MATCH path = ((n:Type))',
                'Syntax exception at line 1 position 23: mismatched input \'<EOF>\' expecting {\'{\', \'(\', \'+\', RETURN, \'*\', WHERE}',
            ],
            [
                'MATCH path=((canary:Plant {id: "02IiNr0GOoKiPMN00sQ5ywcxsF84RQgf2FqD4"})) MATCH (target:Plant {id: "02IiNr0GOoKiPMLzlyajuxj3r003A91sQN0s8"}) RETURN path',
                'Syntax exception at line 1 position 74: missing RETURN at \'MATCH\'',
            ],
            [
                'MATCH path=((canary:Plant {id: "02IiNr0GOoKiPMN00sQ5ywcxsF84RQgf2FqD4"})), (target:Plant {id: "02IiNr0GOoKiPMLzlyajuxj3r003A91sQN0s8"}) RETURN path',
                'Syntax exception at line 1 position 73: mismatched input \',\' expecting {\'{\', \'(\', \'+\', RETURN, \'*\', WHERE}',
            ],
            [
                'MATCH (canary {id: "02IiNr0GOoKiPMMKHq1FVE9PGfXYUHKPwwRDT"})-[rel]-(target:Plant {id: "02IiNr0GOoKiPMLzlyajuxj3r003A91sQN0s8"}) WITH (canary) AS path RETURN path',
                'Syntax exception at line 1 position 128: missing RETURN at \'WITH\'',
            ],
            [
                'MATCH (n) WHERE EXISTS { MATCH (n)-->(:Tag) } RETURN path',
                'Syntax exception at line 1 position 23: mismatched input \'{\' expecting {AND, \':\', \'::\', CONTAINS, \'/\', \'.\', \'||\', ENDS, \'=\', \'>=\', \'>\', IN, IS, \'[\', \'<=\', \'<\', \'-\', \'%\', \'!=\', \'<>\', OR, \'+\', \'^\', \'=~\', RETURN, STARTS, \'*\', XOR}',
            ],
            [
                'MATCH path = shortestPath((a:Plant)-[:IS_MEMBER_OF*..]->(b:Taxon)) RETURN path',
                'Syntax exception at line 1 position 13: extraneous input \'shortestPath\' expecting \'(\'',
            ],
            [
                'MATCH path = ((n:Plant)) CALL { MATCH (n)-->(m:Tag) RETURN m } RETURN path',
                'Syntax exception at line 1 position 25: missing RETURN at \'CALL\'',
            ],
            [
                'MATCH other = ((n:Type)) RETURN other AS path',
                'Syntax exception at line 1 position 38: mismatched input \'AS\' expecting {<EOF>, \';\'}',
            ],
            [
                'MATCH other = ((n:Type)) WITH other AS path RETURN path',
                'Syntax exception at line 1 position 25: missing RETURN at \'WITH\'',
            ],
            [
                'MATCH (p:Plant) RETURN [(p)-->(t:Tag) | t] AS tags',
                'Syntax exception at line 1 position 23: mismatched input \'[\' expecting {ESCAPED_SYMBOLIC_NAME, ACCESS, ACTIVE, ADMIN, ADMINISTRATOR, ALIAS, ALIASES, ALL_SHORTEST_PATHS, ALL, ALTER, AND, ANY, ARRAY, AS, ASC, ASCENDING, ASSIGN, AT, AUTH, BINDINGS, BOOL, BOOLEAN, BOOSTED, BOTH, BREAK, BUILT, BY, CALL, CASCADE, CASE, CHANGE, CIDR, COLLECT, COMMAND, COMMANDS, COMPOSITE, CONCURRENT, CONSTRAINT, CONSTRAINTS, CONTAINS, COPY, CONTINUE, COUNT, CREATE, CSV, CURRENT, DATA, DATABASE, DATABASES, DATE, DATETIME, DBMS, DEALLOCATE, DEFAULT, DEFINED, DELETE, DENY, DESC, DESCENDING, DESTROY, DETACH, DIFFERENT, DISTINCT, DRIVER, DROP, DRYRUN, DUMP, DURATION, EACH, EDGE, ENABLE, ELEMENT, ELEMENTS, ELSE, ENCRYPTED, END, ENDS, EXECUTABLE, EXECUTE, EXIST, EXISTENCE, EXISTS, ERROR, FAIL, FALSE, FIELDTERMINATOR, FINISH, FLOAT, FOR, FOREACH, FROM, FULLTEXT, FUNCTION, FUNCTIONS, GRANT, GRAPH, GRAPHS, GROUP, GROUPS, HEADERS, HOME, ID, IF, IMPERSONATE, IMMUTABLE, IN, INDEX, INDEXES, INF, INFINITY, INSERT, INT, INTEGER, IS, JOIN, KEY, LABEL, LABELS, LEADING, LIMITROWS, LIST, LOAD, LOCAL, LOOKUP, MANAGEMENT, MAP, MATCH, MERGE, NAME, NAMES, NAN, NFC, NFD, NFKC, NFKD, NEW, NODE, NODETACH, NODES, NONE, NORMALIZE, NORMALIZED, NOT, NOTHING, NOWAIT, NULL, OF, OFFSET, ON, ONLY, OPTIONAL, OPTIONS, OPTION, OR, ORDER, PASSWORD, PASSWORDS, PATH, PATHS, PLAINTEXT, POINT, POPULATED, PRIMARY, PRIMARIES, PRIVILEGE, PRIVILEGES, PROCEDURE, PROCEDURES, PROPERTIES, PROPERTY, PROVIDER, PROVIDERS, RANGE, READ, REALLOCATE, REDUCE, RENAME, REL, RELATIONSHIP, RELATIONSHIPS, REMOVE, REPEATABLE, REPLACE, REPORT, REQUIRE, REQUIRED, RESTRICT, RETURN, REVOKE, ROLE, ROLES, ROW, ROWS, SCAN, SEC, SECOND, SECONDARY, SECONDARIES, SECONDS, SEEK, SERVER, SERVERS, SET, SETTING, SETTINGS, SHORTEST_PATH, SHORTEST, SHOW, SIGNED, SINGLE, SKIPROWS, START, STARTS, STATUS, STOP, STRING, SUPPORTED, SUSPENDED, TARGET, TERMINATE, TEXT, THEN, TIME, TIMESTAMP, TIMEZONE, TO, TOPOLOGY, TRAILING, TRANSACTION, TRANSACTIONS, TRAVERSE, TRIM, TRUE, TYPE, TYPED, TYPES, UNION, UNIQUE, UNIQUENESS, UNWIND, URL, USE, USER, USERS, USING, VALUE, VARCHAR, VECTOR, VERTEX, WAIT, WHEN, WHERE, WITH, WITHOUT, WRITE, XOR, YIELD, ZONE, ZONED, IDENTIFIER}',
            ],
            [
                'MATCH path=((plant:Plant WHERE lower(plant.name) = "passionflower")) RETURN path',
                'Syntax exception at line 1 position 36: missing \')\' at \'(\'',
            ],
            [
                'UNWIND ["02IiNr0GOoKiPMLzlyajuxj3r003A91sQN0s8"] AS targetId UNWIND ["02IiNr0GOoKiPMMKHq1FVE9PGfXYUHKPwwRDT"] AS startId MATCH path=(({id: startId})-[]->({id: targetId})) RETURN path',
                'Syntax exception at line 1 position 7: extraneous input \'[\' expecting {DECIMAL_DOUBLE, UNSIGNED_DECIMAL_INTEGER, UNSIGNED_HEX_INTEGER, UNSIGNED_OCTAL_INTEGER, STRING_LITERAL1, STRING_LITERAL2, ESCAPED_SYMBOLIC_NAME, ACCESS, ACTIVE, ADMIN, ADMINISTRATOR, ALIAS, ALIASES, ALL_SHORTEST_PATHS, ALL, ALTER, AND, ANY, ARRAY, AS, ASC, ASCENDING, ASSIGN, AT, AUTH, BINDINGS, BOOL, BOOLEAN, BOOSTED, BOTH, BREAK, BUILT, BY, CALL, CASCADE, CASE, CHANGE, CIDR, COLLECT, COMMAND, COMMANDS, COMPOSITE, CONCURRENT, CONSTRAINT, CONSTRAINTS, CONTAINS, COPY, CONTINUE, COUNT, CREATE, CSV, CURRENT, DATA, DATABASE, DATABASES, DATE, DATETIME, DBMS, DEALLOCATE, DEFAULT, DEFINED, DELETE, DENY, DESC, DESCENDING, DESTROY, DETACH, DIFFERENT, \'$\', DISTINCT, DRIVER, DROP, DRYRUN, DUMP, DURATION, EACH, EDGE, ENABLE, ELEMENT, ELEMENTS, ELSE, ENCRYPTED, END, ENDS, EXECUTABLE, EXECUTE, EXIST, EXISTENCE, EXISTS, ERROR, FAIL, FALSE, FIELDTERMINATOR, FINISH, FLOAT, FOR, FOREACH, FROM, FULLTEXT, FUNCTION, FUNCTIONS, GRANT, GRAPH, GRAPHS, GROUP, GROUPS, HEADERS, HOME, ID, IF, IMPERSONATE, IMMUTABLE, IN, INDEX, INDEXES, INF, INFINITY, INSERT, INT, INTEGER, IS, JOIN, KEY, LABEL, LABELS, \'{\', LEADING, LIMITROWS, LIST, LOAD, LOCAL, LOOKUP, \'(\', MANAGEMENT, MAP, MATCH, MERGE, \'-\', NAME, NAMES, NAN, NFC, NFD, NFKC, NFKD, NEW, NODE, NODETACH, NODES, NONE, NORMALIZE, NORMALIZED, NOT, NOTHING, NOWAIT, NULL, OF, OFFSET, ON, ONLY, OPTIONAL, OPTIONS, OPTION, OR, ORDER, PASSWORD, PASSWORDS, PATH, PATHS, PLAINTEXT, \'+\', POINT, POPULATED, PRIMARY, PRIMARIES, PRIVILEGE, PRIVILEGES, PROCEDURE, PROCEDURES, PROPERTIES, PROPERTY, PROVIDER, PROVIDERS, RANGE, READ, REALLOCATE, REDUCE, RENAME, REL, RELATIONSHIP, RELATIONSHIPS, REMOVE, REPEATABLE, REPLACE, REPORT, REQUIRE, REQUIRED, RESTRICT, RETURN, REVOKE, ROLE, ROLES, ROW, ROWS, SCAN, SEC, SECOND, SECONDARY, SECONDARIES, SECONDS, SEEK, SERVER, SERVERS, SET, SETTING, SETTINGS, SHORTEST_PATH, SHORTEST, SHOW, SIGNED, SINGLE, SKIPROWS, START, STARTS, STATUS, STOP, STRING, SUPPORTED, SUSPENDED, TARGET, TERMINATE, TEXT, THEN, TIME, TIMESTAMP, TIMEZONE, TO, TOPOLOGY, TRAILING, TRANSACTION, TRANSACTIONS, TRAVERSE, TRIM, TRUE, TYPE, TYPED, TYPES, UNION, UNIQUE, UNIQUENESS, UNWIND, URL, USE, USER, USERS, USING, VALUE, VARCHAR, VECTOR, VERTEX, WAIT, WHEN, WHERE, WITH, WITHOUT, WRITE, XOR, YIELD, ZONE, ZONED, IDENTIFIER}',
            ],
            [
                'MATCH path = (p:Plant)-[:IS_MEMBER_OF]->(t:Taxon) WHERE (p)-->(:Tag {name: "blue"}) RETURN path',
                'Syntax exception at line 1 position 61: no viable alternative at input \'->\'',
            ],
        ];
    }

    #[DataProvider('invalidQueryProvider')]
    public function testInvalidQueriesThrowException(string $query, string $detail): void
    {
        $cypherPathSubsetGrammar = $this->buildCypherPathSubsetGrammar(true);

        try {
            $cypherPathSubsetGrammar->validateQuery($query);
            $this->fail();
        } catch (Exception $e) {
            $this->assertSame($detail, $e->getDetail());
        }
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function forbiddenConstructs(): iterable
    {
        yield 'internal property' => ['MATCH path=(n:User) WHERE n._passwordHash = "x" RETURN path', "Path query is not allowed to use the internal property '_passwordHash'."];
        yield 'escaped internal property' => ['MATCH path=(n:User) WHERE n.`_passwordHash` = "x" RETURN path', "Path query is not allowed to use the internal property '_passwordHash'."];
        $hash = "Path query is not allowed to use the internal property 'hash'.";
        yield 'token hash' => ['MATCH path=(n:Token) WHERE n.hash = "x" RETURN path', $hash];
        yield 'token hash in label set' => ['MATCH path=(n:Data|Token) WHERE n.hash = "x" RETURN path', $hash];
        yield 'token hash in label group' => ['MATCH path=(n:(Data|Token)) WHERE n.hash = "x" RETURN path', $hash];
        yield 'token hash with IS' => ['MATCH path=(n IS Token) WHERE n.hash = "x" RETURN path', $hash];
        yield 'token hash escaped label' => ['MATCH path=(n:`Token`) WHERE n.hash = "x" RETURN path', $hash];
        yield 'hash without label' => ['MATCH path=(n) WHERE n.hash = "x" RETURN path', $hash];
        yield 'hash with wildcard label' => ['MATCH path=(n:%) WHERE n.hash = "x" RETURN path', $hash];
        yield 'hash with negated label' => ['MATCH path=(n:!Data) WHERE n.hash = "x" RETURN path', $hash];
        yield 'hash on unknown variable' => ['UNWIND $items AS el MATCH path=(n:Data) WHERE el.hash = "x" RETURN path', $hash];
        yield 'hash on variable also bound as token' => ['MATCH path=(n:Data)-[]->(n:Token) WHERE n.hash = "x" RETURN path', $hash];
        yield 'hash on path' => ['MATCH path=(n:Data) WHERE path.hash = "x" RETURN path', $hash];
        yield 'hash in token pattern' => ['MATCH path=(n:Token {hash: "x"}) RETURN path', $hash];
        yield 'hash in unlabeled pattern' => ['MATCH path=(n {hash: "x"}) RETURN path', $hash];
        yield 'hash in map literal' => ['MATCH path=(n:Data) WHERE n.a = {hash: "x"} RETURN path', $hash];
        yield 'hash on chained access' => ['MATCH path=(n:Data) WHERE n.a.hash = "x" RETURN path', $hash];
        yield 'internal property in pattern' => ['MATCH path=(n:User {_passwordHash: "x"}) RETURN path', "Path query is not allowed to use the internal property '_passwordHash'."];
        yield 'dynamic property' => ['MATCH path=(n:User) WHERE n[$key] = "x" RETURN path', 'Path query is not allowed to use dynamic property access; index with an integer literal only.'];
        yield 'parameter as property map' => ['MATCH path=(n:User $props) RETURN path', 'Path query is not allowed to use a parameter as property map; list the properties instead.'];
    }

    #[DataProvider('forbiddenConstructs')]
    public function testForbiddenConstructsThrowException(string $query, string $detail): void
    {
        $cypherPathSubsetGrammar = $this->buildCypherPathSubsetGrammar(true);

        try {
            $cypherPathSubsetGrammar->validateQuery($query);
            $this->fail();
        } catch (Exception $e) {
            $this->assertSame($detail, $e->getDetail());
        }
    }

    public function testQueryWithMoreThanOneMatchClauseThrowsException(): void
    {
        $cypherPathSubsetGrammar = $this->buildCypherPathSubsetGrammar(true);

        $query = 'MATCH path=((canary:Plant {id: "02IiNr0GOoKiPMN00sQ5ywcxsF84RQgf2FqD4"})) MATCH (target:Plant {id: "02IiNr0GOoKiPMLzlyajuxj3r003A91sQN0s8"}) RETURN path;';

        try {
            $cypherPathSubsetGrammar->validateQuery($query);
            $this->fail();
        } catch (Exception $e) {
            $this->assertSame('Syntax exception at line 1 position 74: missing RETURN at \'MATCH\'', $e->getDetail());
        }
    }

    public function testExpensiveQueryCheckIsSkippedIfQueryIsCached(): void
    {
        $query = 'MATCH path = ((:Type)) RETURN path';
        $redisKey = 'validated-query:cypher-path-subset:51a3a1e5ab8c30bc819ac03753ded3f272c709274383d98e7d90be35a4a4046f'; // sha256 hash of query

        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient
            ->exists(Argument::is($redisKey))
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $cypherPathSubsetGrammar = $this->buildCypherPathSubsetGrammar(
            redisClient: $redisClient->reveal()
        );

        $result = $cypherPathSubsetGrammar->validateQuery($query);
        $this->assertTrue($result);
    }

    /**
     * @param ObjectProphecy<RedisClient> $redis
     */
    private function buildPreparingGrammar(ObjectProphecy $redis): CypherPathSubsetGrammar
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->willReturn('https://mock.dev/123');
        $stopwatch = $this->prophesize(Stopwatch::class);
        $stopwatch->start(Argument::any())->willReturn($this->prophesize(StopwatchEvent::class)->reveal());
        $stopwatch->stop(Argument::any())->willReturn($this->prophesize(StopwatchEvent::class)->reveal());

        return new CypherPathSubsetGrammar(
            new AntlrSyntaxErrorListenerFactory(new Client400BadGrammarExceptionFactory($urlGenerator->reveal())),
            $stopwatch->reveal(),
            $redis->reveal(),
            new RedisKeyFactory(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            new Client400BadContentExceptionFactory($urlGenerator->reveal()),
        );
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function rewriteProvider(): array
    {
        $ids = CypherPathSubsetGrammar::IDS_RETURN_ITEMS;

        return [
            'plain' => ['MATCH path = ((:Type)) RETURN path', 'MATCH path = ((:Type)) RETURN '.$ids],
            'escaped' => ['MATCH path = ((:Type)) RETURN `path`;', 'MATCH path = ((:Type)) RETURN '.$ids.';'],
            'limit' => ['MATCH path = ((:Type)) RETURN path LIMIT 10', 'MATCH path = ((:Type)) RETURN '.$ids.' LIMIT 10'],
            'skip and limit' => ['MATCH path = ((:Type)) RETURN path SKIP 5 LIMIT 10', 'MATCH path = ((:Type)) RETURN '.$ids.' SKIP 5 LIMIT 10'],
            'multibyte characters before the return' => ['MATCH path = ((:Type {name: "äö€"})) RETURN path', 'MATCH path = ((:Type {name: "äö€"})) RETURN '.$ids],
            'distinct stays as it is' => ['MATCH path = ((:Type)) RETURN DISTINCT path', 'MATCH path = ((:Type)) RETURN DISTINCT path'],
        ];
    }

    #[DataProvider('rewriteProvider')]
    public function testPrepareQueryReplacesThePathByItsIdsWhereItIsSafeAndCachesTheResult(string $query, string $expected): void
    {
        $redis = $this->prophesize(RedisClient::class);
        $redis->get(Argument::any())->willReturn(null);
        $redis->set(Argument::any(), Argument::that(static fn (mixed $value): bool => $value === $expected), 'EX', 3600)->shouldBeCalledOnce();
        $redis->set(Argument::any(), Argument::that(static fn (mixed $value): bool => true === $value), 'EX', 3600)->shouldBeCalledOnce();

        $this->assertSame($expected, $this->buildPreparingGrammar($redis)->prepareQuery($query));
    }

    public function testPrepareQueryUsesTheCachedQueryWithoutParsing(): void
    {
        $redis = $this->prophesize(RedisClient::class);
        $redis->get(Argument::any())->willReturn('cached query');
        $redis->set(Argument::cetera())->shouldNotBeCalled();

        $this->assertSame('cached query', $this->buildPreparingGrammar($redis)->prepareQuery('this is not even cypher'));
    }

    public function testPrepareQueryRejectsInvalidQueries(): void
    {
        $redis = $this->prophesize(RedisClient::class);
        $redis->get(Argument::any())->willReturn(null);
        $redis->set(Argument::cetera())->shouldNotBeCalled();

        $this->expectException(Exception::class);
        $this->buildPreparingGrammar($redis)->prepareQuery('MATCH (n:Type) RETURN n');
    }
}
