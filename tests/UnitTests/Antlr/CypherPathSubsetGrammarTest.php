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
            ['MATCH path = ((n:Type)) WHERE n.id = "00000000-0000-0000-0000-000000000000" RETURN path'],
            ['MATCH path=((:Plant)-[:HAS_TAG]->(:Tag {name: $color})) RETURN path'],
            ['UNWIND $stepResults[0].elements AS el MATCH path=((plant:Plant)-[:IS_MEMBER_OF*..]->(:Taxon)) WHERE plant.id = el.id RETURN path'],
            ['MATCH path=((:Plant)-[:IS_MEMBER_OF*..]->(t:Taxon)) WHERE t.name IN $taxonNames RETURN path LIMIT 5'],
            ['MATCH path=((plant:Plant WHERE plant.name = "Passionflower")) RETURN path'],
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
                'MATCH path=((canary:Plant {id: "f06bde24-337f-497c-825e-c70f866485d0"})) MATCH (target:Plant {id: "70965ab5-bc3e-4bbe-aacf-1b7e12b76110"}) RETURN path',
                'Syntax exception at line 1 position 73: missing RETURN at \'MATCH\'',
            ],
            [
                'MATCH path=((canary:Plant {id: "f06bde24-337f-497c-825e-c70f866485d0"})), (target:Plant {id: "70965ab5-bc3e-4bbe-aacf-1b7e12b76110"}) RETURN path',
                'Syntax exception at line 1 position 72: mismatched input \',\' expecting {\'{\', \'(\', \'+\', RETURN, \'*\', WHERE}',
            ],
            [
                'MATCH (canary {id: "9abc0ab3-fa1a-4138-80c1-49db5c4d082f"})-[rel]-(target:Plant {id: "70965ab5-bc3e-4bbe-aacf-1b7e12b76110"}) WITH (canary) AS path RETURN path',
                'Syntax exception at line 1 position 126: missing RETURN at \'WITH\'',
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
                'UNWIND ["70965ab5-bc3e-4bbe-aacf-1b7e12b76110"] AS targetId UNWIND ["9abc0ab3-fa1a-4138-80c1-49db5c4d082f"] AS startId MATCH path=(({id: startId})-[]->({id: targetId})) RETURN path',
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

    public function testQueryWithMoreThanOneMatchClauseThrowsException(): void
    {
        $cypherPathSubsetGrammar = $this->buildCypherPathSubsetGrammar(true);

        $query = 'MATCH path=((canary:Plant {id: "f06bde24-337f-497c-825e-c70f866485d0"})) MATCH (target:Plant {id: "70965ab5-bc3e-4bbe-aacf-1b7e12b76110"}) RETURN path;';

        try {
            $cypherPathSubsetGrammar->validateQuery($query);
            $this->fail();
        } catch (Exception $e) {
            $this->assertSame('Syntax exception at line 1 position 73: missing RETURN at \'MATCH\'', $e->getDetail());
        }
    }

    public function testExpensiveQueryCheckIsSkippedIfQueryIsCached(): void
    {
        $query = 'MATCH path = ((:Type)) RETURN path';
        $redisKey = 'validated-query:cypher-path-subset:5d76b75c07ec0ce124c70b158c8e1809b05161f1'; // sha1 hash of query

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
}
