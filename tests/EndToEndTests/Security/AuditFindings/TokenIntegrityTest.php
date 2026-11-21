<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\AuditFindings;

use App\EventSystem\ElementPropertyChange\EventListener\TokenElementPropertyChangeEventListener;
use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Token nodes store the SHA3-256 hash of the secret in `hash`, the lifecycle in `state` and `expirationDate`. None of
 * them may be written through the element endpoints (whatever the method), and the authentication has to honour the
 * expiration date.
 */
class TokenIntegrityTest extends BaseRequestTestCase
{
    use AuditHelperTrait;

    private function tokenNodeId(string $userId, string $secretToken): string
    {
        $row = $this->getCypherClient()->run(
            'MATCH (:User {id: $userId})-[:OWNS]->(t:Token {hash: $hash}) RETURN t.id AS id',
            ['userId' => $userId, 'hash' => hash('sha3-256', $secretToken)]
        )->first();

        return (string) $row->get('id');
    }

    /**
     * @return array<string, mixed>
     */
    private function storedToken(string $tokenId): array
    {
        $row = $this->getCypherClient()->run('MATCH (t:Token {id: $id}) RETURN properties(t) AS p', ['id' => $tokenId])->first();

        return $row->get('p')->toArray();
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function protectedProperties(): iterable
    {
        $values = [
            'hash' => hash('sha3-256', 'secret-token:chosen-by-the-caller'),
            'state' => 'ACTIVE',
            'expirationDate' => '2999-01-01T00:00:00+00:00',
            'token' => 'secret-token:chosen-by-the-caller',
            '_tokenHash' => 'x',
        ];
        foreach (TokenElementPropertyChangeEventListener::PROTECTED_PROPERTIES as $property) {
            yield $property => [$property, $values[$property]];
        }
    }

    #[DataProvider('protectedProperties')]
    public function testProtectedPropertyCanNotBeWrittenWithPatch(string $property, mixed $value): void
    {
        $user = $this->registerUser('token-patch');
        $tokenId = $this->tokenNodeId($user['id'], $user['token']);
        $before = $this->storedToken($tokenId);

        $response = $this->runPatchRequest('/'.$tokenId, $user['token'], [$property => $value]);

        $this->assertSame(400, $response->getStatusCode(), sprintf('PATCH of Token.%s was accepted', $property));
        $this->assertEquals($before[$property] ?? null, $this->storedToken($tokenId)[$property] ?? null);
    }

    #[DataProvider('protectedProperties')]
    public function testProtectedPropertyCanNotBeWrittenWithPut(string $property, mixed $value): void
    {
        $user = $this->registerUser('token-put');
        $tokenId = $this->tokenNodeId($user['id'], $user['token']);
        $before = $this->storedToken($tokenId);

        $response = $this->runPutRequest('/'.$tokenId, $user['token'], ['name' => 'renamed', $property => $value]);

        $this->assertSame(400, $response->getStatusCode(), sprintf('PUT of Token.%s was accepted', $property));
        $this->assertEquals($before[$property] ?? null, $this->storedToken($tokenId)[$property] ?? null);
    }

    #[DataProvider('protectedProperties')]
    public function testPutWithoutProtectedPropertyKeepsThem(string $property, mixed $value = null): void
    {
        $user = $this->registerUser('token-put-keep');
        $tokenId = $this->tokenNodeId($user['id'], $user['token']);
        $before = $this->storedToken($tokenId);

        $response = $this->runPutRequest('/'.$tokenId, $user['token'], ['name' => 'renamed']);

        $this->assertSame(204, $response->getStatusCode());
        $this->assertEquals($before[$property] ?? null, $this->storedToken($tokenId)[$property] ?? null, sprintf('PUT dropped or changed Token.%s', $property));
        $this->assertSame(200, $this->runGetRequest('/me', $user['token'])->getStatusCode());
    }

    #[DataProvider('protectedProperties')]
    public function testTokenCanNotBeCreatedThroughTheElementEndpoints(string $property, mixed $value): void
    {
        $user = $this->registerUser('token-create');
        $parent = $this->createNodeAs($user['token']);

        $this->assertSame(400, $this->runPostRequest('/', $user['token'], ['type' => 'Token', 'data' => [$property => $value]])->getStatusCode());
        $this->assertSame(400, $this->runPostRequest('/'.$parent, $user['token'], ['type' => 'Token', 'data' => [$property => $value]])->getStatusCode());
    }

    #[DataProvider('protectedProperties')]
    public function testProtectedPropertyIsNotReturned(string $property, mixed $value = null): void
    {
        $user = $this->registerUser('token-return');
        $tokenId = $this->tokenNodeId($user['id'], $user['token']);

        $response = $this->runGetRequest('/'.$tokenId, $user['token']);

        $this->assertSame(200, $response->getStatusCode());
        $data = $this->getBody($response)['data'];
        if (in_array($property, ['hash', 'token', '_tokenHash'], true)) {
            $this->assertArrayNotHasKey($property, $data);
        } else {
            $this->assertArrayHasKey($property, $data, 'state and expiration date stay readable');
        }
    }

    public function testRevokedTokenStaysRevoked(): void
    {
        $user = $this->registerUser('token-state');
        $revoked = $this->createTokenFor($user['email']);
        $revokedId = $this->tokenNodeId($user['id'], $revoked);
        $this->getCypherClient()->run('MATCH (t:Token {id: $id}) SET t.state = "REVOKED"', ['id' => $revokedId]);

        $this->runPatchRequest('/'.$revokedId, $user['token'], ['state' => 'ACTIVE']);

        $this->assertTokenIsRejected($revoked, 'a revocation was undone');
    }

    /**
     * Tokens of the reference dataset scenario `security.tokenExpiration`, all of them with state `ACTIVE`. A token with a
     * long past expiration date is not part of it, `cron:expire-tokens` deletes those.
     *
     * @return iterable<string, array{string, int}> token, expected status of an authenticated request
     */
    public static function tokensWithExpiration(): iterable
    {
        yield 'no expiration date' => ['secret-token:Yanzkp3xfO3n6Lsev6NFEx', 200];
        yield 'expiration date in the future' => ['secret-token:zIilRhStgm0FOLBWWFWwK7', 200];
    }

    #[DataProvider('tokensWithExpiration')]
    public function testExpirationDateIsHonouredDuringAuthentication(string $token, int $expectedStatus): void
    {
        $this->assertSame($expectedStatus, $this->runGetRequest('/me', $token)->getStatusCode());
    }

    public function testActiveTokenPastItsExpirationDateIsRejected(): void
    {
        $user = $this->registerUser('token-past-expiration');
        // the token is not used before, so it is not cached
        $this->getCypherClient()->run(
            'MATCH (:User {id: $userId})-[:OWNS]->(t:Token {hash: $hash}) SET t.expirationDate = datetime() - duration("PT1H")',
            ['userId' => $user['id'], 'hash' => hash('sha3-256', $user['token'])]
        );

        $this->assertTokenIsRejected($user['token'], 'a token past its expiration date was accepted (state is still ACTIVE)');
    }
}
