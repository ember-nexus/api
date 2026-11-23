<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\AuditFindings;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Audit finding "search leaks data from the whole database": the `cypher-path-subset` step evaluates the user's
 * conditions against every node and removes inaccessible paths only afterwards. Whether something was removed is
 * visible in the response, which turns `WHERE hidden.property STARTS WITH "x"` into a character by character oracle for
 * every property of every node, e.g. `_passwordHash` or the unique user identifier (the e-mail address).
 */
class SearchSideChannelTest extends BaseRequestTestCase
{
    use AuditHelperTrait;

    /**
     * @return array<string, mixed>
     */
    private function normalize(array $response): array
    {
        $normalized = [];
        foreach ($response as $key => $value) {
            if (in_array($key, ['start', 'end', 'duration', 'query', 'queryExplanation'], true)) {
                continue;
            }
            $normalized[$key] = is_array($value) ? $this->normalize($value) : $value;
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function search(?string $token, string $query, bool $debug = true): array
    {
        $response = $this->runPostRequest('/search', $token, [
            'debug' => $debug,
            'steps' => [['type' => 'cypher-path-subset', 'query' => $query]],
        ]);
        $this->assertSame(200, $response->getStatusCode());

        return $this->normalize($this->getBody($response));
    }

    private const string ATTACKER_TOKEN = 'secret-token:fTI8DEnvEXiTLyCauxZfup';

    /**
     * @return iterable<string, array{?string}>
     */
    public static function callers(): iterable
    {
        yield 'anonymous' => [null];
        yield 'authenticated user' => [self::ATTACKER_TOKEN];
    }

    /**
     * Reference dataset scenario `security.searchSideChannel`: the victim owns a hidden document with the property
     * `secret`, the caller has no access to it.
     *
     * @return iterable<string, array{?string, string, string}>
     */
    public static function conditionsOnHiddenElements(): iterable
    {
        foreach (self::callers() as $callerName => [$caller]) {
            yield $callerName.', exact value' => [$caller, 'secret', 's3cr3t-value-of-the-victim'];
            yield $callerName.', prefix of the value' => [$caller, 'secret', 's3cr3t'];
            yield $callerName.', e-mail of the victim' => [$caller, 'email', 'victim@searchSideChannel.security.localhost.dev'];
        }
    }

    #[DataProvider('conditionsOnHiddenElements')]
    public function testConditionOnInaccessibleNodeIsNotDistinguishableFromNoMatch(?string $caller, string $property, string $value): void
    {
        $query = 'MATCH path=(n) WHERE n.%s STARTS WITH "%s" RETURN path';

        $existing = $this->search($caller, sprintf($query, $property, $value));
        $missing = $this->search($caller, sprintf($query, $property, 'does-not-exist-'.$value));

        $this->assertSame([], $existing['results']['paths']);
        $this->assertSame(
            $this->withoutInputs($missing),
            $this->withoutInputs($existing),
            'the response reveals whether a node the caller can not access matched the query'
        );
    }

    /**
     * @param array<string, mixed> $normalized
     *
     * @return array<string, mixed>
     */
    private function withoutInputs(array $normalized): array
    {
        foreach (array_keys($normalized['debug'] ?? []) as $index) {
            unset($normalized['debug'][$index]['input']);
        }

        return $normalized;
    }
}
