<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The `cypher-path-subset` step rejects the token property `hash`, as conditions on it would reveal token hashes
 * character by character. The restriction only applies to elements which can be tokens: nodes with the label `Token`
 * and nodes whose label is not known (no label expression, wildcard or negation). Other nodes and relations can use
 * properties named `hash`.
 *
 * Reference dataset scenario `security.hashProperty`: the owner owns a token, two `Data` nodes and a `Tag` node with
 * their own `hash` property and a `RELATED` relation between the `Data` nodes, which also has one.
 */
class CypherHashPropertyTest extends BaseRequestTestCase
{
    private const string OWNER_TOKEN = 'secret-token:vV4C66wRfZzIXCIctZm2De';
    private const string DATA_1 = '02IiNr0GOuahwcW5CInRgD231CrK4G7nTWoWS';
    private const string DATA_2 = '02IiNr0GOuahwcWezNwe87HZMLhQXFvPhvSWO';
    private const string TAG = '02IiNr0GOuahwcVCkRWoCCuIwNk1UQ2Wz4vQL';

    /**
     * @return iterable<string, array{string, list<list<string>>}>
     */
    public static function acceptedQueries(): iterable
    {
        yield 'data node, WHERE' => ['MATCH path=(n:Data) WHERE n.hash = "data-hash-1" RETURN path', [[self::DATA_1]]];
        yield 'data node, escaped property name' => ['MATCH path=(n:Data) WHERE n.`hash` = "data-hash-1" RETURN path', [[self::DATA_1]]];
        yield 'data node, other case of property name' => ['MATCH path=(n:Data) WHERE n.HASH = "data-hash-1" RETURN path', []];
        yield 'data node, property map' => ['MATCH path=(n:Data {hash: "data-hash-2"}) RETURN path', [[self::DATA_2]]];
        yield 'data node, WHERE in pattern' => ['MATCH path=(n:Data WHERE n.hash = "data-hash-2") RETURN path', [[self::DATA_2]]];
        yield 'data node, IS' => ['MATCH path=(n IS Data) WHERE n.hash = "data-hash-1" RETURN path', [[self::DATA_1]]];
        yield 'tag node' => ['MATCH path=(n:Tag) WHERE n.hash STARTS WITH "tag-hash" RETURN path', [[self::TAG]]];
        yield 'label set without token' => ['MATCH path=(n:Data|Tag) WHERE n.hash = "tag-hash-1" RETURN path', [[self::TAG]]];
        yield 'label group without token' => ['MATCH path=(n:(Data|Tag)) WHERE n.hash = "tag-hash-1" RETURN path', [[self::TAG]]];
        yield 'label conjunction without token' => ['MATCH path=(n:Data&Tag) WHERE n.hash = "tag-hash-1" RETURN path', []];
        yield 'hash in ORDER BY' => ['MATCH path=(n:Data) WHERE n.scenario = "security.hashProperty" RETURN path ORDER BY n.hash DESC', [[self::DATA_2], [self::DATA_1]]];
        yield 'both nodes of a path' => ['MATCH path=(a:Data)-[:RELATED]->(b:Data) WHERE a.hash = "data-hash-1" AND b.hash = "data-hash-2" RETURN path', [[self::DATA_1, self::DATA_2]]];
        yield 'relation, WHERE' => ['MATCH path=(a:Data)-[r:RELATED]->(b:Data) WHERE r.hash = "relation-hash-1" RETURN path', [[self::DATA_1, self::DATA_2]]];
        yield 'relation, property map' => ['MATCH path=(a:Data)-[:RELATED {hash: "relation-hash-1"}]->(b:Data) RETURN path', [[self::DATA_1, self::DATA_2]]];
        yield 'relation without type, WHERE in pattern' => ['MATCH path=(a:Data)-[r WHERE r.hash = "relation-hash-1"]->(b:Data) RETURN path', [[self::DATA_1, self::DATA_2]]];
        yield 'node variable which is used again' => ['MATCH path=(n:Data)-[:RELATED]->(:Data)<-[:RELATED]-(n) WHERE n.hash = "data-hash-1" RETURN path', []];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function acceptedQueryTexts(): iterable
    {
        foreach (self::acceptedQueries() as $name => [$query]) {
            yield $name => [$query];
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function rejectedQueries(): iterable
    {
        yield 'token' => ['MATCH path=(n:Token) WHERE n.hash STARTS WITH "1" RETURN path'];
        yield 'token, escaped label' => ['MATCH path=(n:`Token`) WHERE n.hash STARTS WITH "1" RETURN path'];
        yield 'token, IS' => ['MATCH path=(n IS Token) WHERE n.hash STARTS WITH "1" RETURN path'];
        yield 'token, property map' => ['MATCH path=(n:Token {hash: "x"}) RETURN path'];
        yield 'token, WHERE in pattern' => ['MATCH path=(n:Token WHERE n.hash STARTS WITH "1") RETURN path'];
        yield 'token, ORDER BY' => ['MATCH path=(n:Token) RETURN path ORDER BY n.hash'];
        yield 'token within label set' => ['MATCH path=(n:Data|Token) WHERE n.hash STARTS WITH "1" RETURN path'];
        yield 'token within label group' => ['MATCH path=(n:(Data|Token)) WHERE n.hash STARTS WITH "1" RETURN path'];
        yield 'token within label conjunction' => ['MATCH path=(n:Data&Token) WHERE n.hash STARTS WITH "1" RETURN path'];
        yield 'token within IS label set' => ['MATCH path=(n IS Data|Token) WHERE n.hash STARTS WITH "1" RETURN path'];
        yield 'no label' => ['MATCH path=(n) WHERE n.hash STARTS WITH "1" RETURN path'];
        yield 'no label, property map' => ['MATCH path=(n {hash: "x"}) RETURN path'];
        yield 'wildcard label' => ['MATCH path=(n:%) WHERE n.hash STARTS WITH "1" RETURN path'];
        yield 'negated label' => ['MATCH path=(n:!Data) WHERE n.hash STARTS WITH "1" RETURN path'];
        yield 'negated token label' => ['MATCH path=(n:!Token) WHERE n.hash STARTS WITH "1" RETURN path'];
        yield 'other node of the path has no label' => ['MATCH path=(a:Data)-[:OWNS]->(b) WHERE b.hash STARTS WITH "1" RETURN path'];
        yield 'variable is also bound as token' => ['MATCH path=(n:Data)-[:OWNS]->(n:Token) WHERE n.hash STARTS WITH "1" RETURN path'];
        yield 'path variable' => ['MATCH path=(n:Data) WHERE path.hash STARTS WITH "1" RETURN path'];
        yield 'chained access' => ['MATCH path=(n:Data) WHERE n.a.hash STARTS WITH "1" RETURN path'];
        yield 'unknown variable' => ['UNWIND $items AS item MATCH path=(n:Data) WHERE item.hash STARTS WITH "1" RETURN path'];
        yield 'map literal' => ['MATCH path=(n:Data) WHERE {hash: 1}.hash = 1 RETURN path'];
    }

    /**
     * @return array<string, mixed>
     */
    private function search(?string $token, string $query): array
    {
        $step = ['type' => 'cypher-path-subset', 'query' => $query];
        if (str_contains($query, '$items')) {
            $step['parameters'] = ['items' => [['hash' => 'x']]];
        }

        $response = $this->runPostRequest('/search', $token, ['steps' => [$step]]);
        $this->assertContains($response->getStatusCode(), [200, 400], $query);

        return ['status' => $response->getStatusCode(), 'body' => $this->getBody($response)];
    }

    /**
     * @param list<list<string>> $expectedNodeIds
     */
    #[DataProvider('acceptedQueries')]
    public function testQueryOnNonTokenElementsIsAccepted(string $query, array $expectedNodeIds): void
    {
        $result = $this->search(self::OWNER_TOKEN, $query);

        $this->assertSame(200, $result['status'], $query);
        $nodeIds = array_map(static fn (array $path): array => $path['nodeIds'], $result['body']['results']['paths']);
        $this->assertSame($expectedNodeIds, $nodeIds, $query);
    }

    #[DataProvider('acceptedQueryTexts')]
    public function testAcceptedQueryFindsNothingForCallerWithoutAccess(string $query): void
    {
        $result = $this->search(null, $query);

        $this->assertSame(200, $result['status'], $query);
        $this->assertSame([], $result['body']['results']['paths'], $query);
    }

    #[DataProvider('rejectedQueries')]
    public function testQueryWhichCouldReadTokenHashesIsRejected(string $query): void
    {
        $this->assertSame(400, $this->search(self::OWNER_TOKEN, $query)['status'], $query);
        $this->assertSame(400, $this->search(null, $query)['status'], $query);
    }
}
