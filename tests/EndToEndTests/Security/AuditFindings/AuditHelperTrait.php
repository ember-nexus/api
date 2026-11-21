<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security\AuditFindings;

use App\Type\Uuid;

/**
 * Shared set-up for the security audit regression tests: every test works with freshly registered users, so no test
 * depends on (or modifies) the reference dataset.
 *
 * @mixin \App\Tests\EndToEndTests\BaseRequestTestCase
 */
trait AuditHelperTrait
{
    /**
     * @return array{id: string, email: string, token: string}
     */
    private function registerUser(string $prefix = 'audit'): array
    {
        $email = sprintf('%s-%s@localhost.dev', $prefix, bin2hex(random_bytes(6)));
        $response = $this->runPostRequest('/register', null, [
            'type' => 'User',
            'password' => '1234',
            'uniqueUserIdentifier' => $email,
        ]);
        $this->assertIsCreatedResponse($response);
        $userId = $this->getUuidFromLocation($response);

        return ['id' => $userId, 'email' => $email, 'token' => $this->createTokenFor($email)];
    }

    private function createTokenFor(string $email, string $password = '1234'): string
    {
        $tokenResponse = $this->runPostRequest('/token', null, [
            'type' => 'Token',
            'uniqueUserIdentifier' => $email,
            'password' => $password,
        ]);
        $this->assertSame(201, $tokenResponse->getStatusCode());

        return $this->getBody($tokenResponse)['token'];
    }

    private function createNodeAs(string $token, string $type = 'Data', string $name = 'audit node', string $parentPath = '/'): string
    {
        $response = $this->runPostRequest($parentPath, $token, ['type' => $type, 'data' => ['name' => $name]]);
        $this->assertIsCreatedResponse($response);

        return $this->getUuidFromLocation($response);
    }

    /**
     * Writes an access relation straight into the graph, as a test precondition (the API is the thing under test).
     *
     * @param array<string, mixed> $properties
     */
    private function grantInGraph(string $relationType, string $startId, string $endId, array $properties = []): void
    {
        $this->getCypherClient()->run(
            sprintf(
                'MATCH (s {id: $startId}), (e {id: $endId}) '.
                'CREATE (s)-[r:%s {id: $relationId, created: datetime(), updated: datetime()}]->(e)%s',
                $relationType,
                [] === $properties ? '' : ' SET r += $properties'
            ),
            [
                'startId' => $startId,
                'endId' => $endId,
                'relationId' => Uuid::generate()->toString(),
                ...([] === $properties ? [] : ['properties' => $properties]),
            ]
        );
    }

    private function countRelationsInGraph(string $type, string $startId, string $endId): int
    {
        $row = $this->getCypherClient()->run(
            sprintf('MATCH ({id: $startId})-[r:%s]->({id: $endId}) RETURN count(r) AS c', $type),
            ['startId' => $startId, 'endId' => $endId]
        )->first();

        return (int) $row->get('c');
    }

    private function assertTokenIsRejected(string $token, string $message): void
    {
        $response = $this->runGetRequest('/me', $token);
        $this->assertSame(401, $response->getStatusCode(), $message);
    }
}
