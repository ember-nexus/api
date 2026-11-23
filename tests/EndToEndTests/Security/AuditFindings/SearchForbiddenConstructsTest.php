<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\AuditFindings;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * The `cypher-path-subset` step evaluates conditions on every node. Constructs which could read internal properties
 * (`_passwordHash`, token `hash`, ...) or which make the database traverse without bound are rejected with 400 before
 * the query runs.
 */
class SearchForbiddenConstructsTest extends BaseRequestTestCase
{
    use AuditHelperTrait;

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function rejectedQueries(): iterable
    {
        // internal properties, every way of naming them
        yield 'internal property in WHERE' => ['MATCH path=(n:User) WHERE n._passwordHash STARTS WITH "$" RETURN path', []];
        yield 'internal property, escaped name' => ['MATCH path=(n:User) WHERE n.`_passwordHash` STARTS WITH "$" RETURN path', []];
        yield 'internal property, escape sequence' => ['MATCH path=(n:User) WHERE n.`_passwordHash` STARTS WITH "$" RETURN path', []];
        yield 'any underscore property' => ['MATCH path=(n:User) WHERE n._anything IS NOT NULL RETURN path', []];
        yield 'token hash' => ['MATCH path=(n:Token) WHERE n.hash STARTS WITH "a" RETURN path', []];
        yield 'token hash, other case' => ['MATCH path=(n:Token) WHERE n.HASH STARTS WITH "a" RETURN path', []];
        yield 'token hash, escaped' => ['MATCH path=(n:Token) WHERE n.`hash` STARTS WITH "a" RETURN path', []];
        yield 'internal property in ORDER BY' => ['MATCH path=(n:User) RETURN path ORDER BY n._passwordHash', []];
        yield 'internal property in node pattern' => ['MATCH path=(n:User {_passwordHash: "x"}) RETURN path', []];
        yield 'token hash in node pattern' => ['MATCH path=(n:Token {hash: "x"}) RETURN path', []];
        yield 'internal property in relationship pattern' => ['MATCH path=(a)-[r:OWNS {_x: 1}]->(b) RETURN path', []];
        yield 'internal property in relationship WHERE' => ['MATCH path=(a)-[r:OWNS WHERE r._x = 1]->(b) RETURN path', []];
        yield 'internal property in map literal comparison' => ['MATCH path=(n:User) WHERE n.id = {_x: 1}.id RETURN path', []];

        // computed property names
        yield 'dynamic property by string' => ['MATCH path=(n:User) WHERE n["_passwordHash"] STARTS WITH "$" RETURN path', []];
        yield 'dynamic property by parameter' => ['MATCH path=(n:User) WHERE n[$key] STARTS WITH "$" RETURN path', ['key' => '_passwordHash']];
        yield 'dynamic property in ORDER BY' => ['MATCH path=(n:User) RETURN path ORDER BY n[$key]', ['key' => '_passwordHash']];
        yield 'parameter as property map' => ['MATCH path=(n:User $props) RETURN path', ['props' => ['_passwordHash' => 'x']]];
        yield 'parameter as relationship property map' => ['MATCH path=(a)-[r:OWNS $props]->(b) RETURN path', ['props' => ['_x' => 'x']]];
        yield 'map projection' => ['MATCH path=(n:User) WHERE n {.*} = {} RETURN path', []];
        yield 'map projection of one property' => ['MATCH path=(n:User) WHERE n {._passwordHash}._passwordHash IS NOT NULL RETURN path', []];
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>}>
     */
    public static function acceptedQueries(): iterable
    {
        yield 'plain property' => ['MATCH path=(n:User) WHERE n.email STARTS WITH "nobody-" RETURN path LIMIT 1', []];
        yield 'property map with public property' => ['MATCH path=(n:User {email: $email}) RETURN path', ['email' => 'nobody@localhost.dev']];
        yield 'untyped unbounded star' => ['MATCH path=(a)-[*]->(b) RETURN path LIMIT 1', []];
        yield 'untyped deep star' => ['MATCH path=(a)-[*..9]->(b) RETURN path LIMIT 1', []];
        yield 'typed unbounded star' => ['MATCH path=(a)-[:OWNS*]->(b) RETURN path LIMIT 1', []];
        yield 'typed star without upper bound' => ['MATCH path=(a)-[:OWNS*2..]->(b) RETURN path LIMIT 1', []];
        yield 'typed star above the limit' => ['MATCH path=(a)-[:OWNS*..9]->(b) RETURN path LIMIT 1', []];
        yield 'unbounded quantifier' => ['MATCH path=(a)-[:OWNS]->+(b) RETURN path LIMIT 1', []];
        yield 'bounded quantifier' => ['MATCH path=(a)-[:OWNS]->{1,3}(b) RETURN path LIMIT 1', []];
        yield 'bounded star' => ['MATCH path=(a)-[*..3]->(b) RETURN path LIMIT 1', []];
        yield 'bounded star range at the limit' => ['MATCH path=(a)-[*1..5]->(b) RETURN path LIMIT 1', []];
        yield 'fixed length' => ['MATCH path=(a)-[*2]->(b) RETURN path LIMIT 1', []];
        yield 'property named like an internal one without prefix' => ['MATCH path=(n:Data) WHERE n.passwordHash IS NULL RETURN path LIMIT 1', []];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function search(?string $token, string $query, array $parameters): int
    {
        $step = ['type' => 'cypher-path-subset', 'query' => $query];
        if ([] !== $parameters) {
            $step['parameters'] = $parameters;
        }

        return $this->runPostRequest('/search', $token, ['steps' => [$step]])->getStatusCode();
    }

    /**
     * @param array<string, mixed> $parameters
     */
    #[DataProvider('rejectedQueries')]
    public function testQueryIsRejectedForAnonymousCaller(string $query, array $parameters): void
    {
        $this->assertSame(400, $this->search(null, $query, $parameters));
    }

    /**
     * @param array<string, mixed> $parameters
     */
    #[DataProvider('rejectedQueries')]
    public function testQueryIsRejectedForAuthenticatedCaller(string $query, array $parameters): void
    {
        $this->assertSame(400, $this->search($this->registerUser('search-forbidden')['token'], $query, $parameters));
    }

    /**
     * @param array<string, mixed> $parameters
     */
    #[DataProvider('acceptedQueries')]
    public function testHarmlessQueryStillWorks(string $query, array $parameters): void
    {
        $this->assertSame(200, $this->search(null, $query, $parameters));
    }
}
