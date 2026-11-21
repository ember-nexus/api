<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Security;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use App\Type\Uuid;

/**
 * `HAS_READ_ACCESS` with `onCreatedByUser` only grants access to elements whose `createdBy` is the user.
 */
class OnCreatedByUserTest extends BaseRequestTestCase
{
    private const string OWNER_TOKEN = 'secret-token:27bYlF6ZPIaGNbC6VT3V41';

    /**
     * @return array{0: string, 1: string} user id and token
     */
    private function registerUserWithToken(): array
    {
        $email = sprintf('on-created-by-user-%s@localhost.dev', bin2hex(random_bytes(6)));
        $response = $this->runPostRequest('/register', null, [
            'type' => 'User',
            'password' => '1234',
            'uniqueUserIdentifier' => $email,
        ]);
        $this->assertIsCreatedResponse($response);
        $userId = $this->getUuidFromLocation($response);

        $tokenResponse = $this->runPostRequest('/token', null, [
            'type' => 'Token',
            'uniqueUserIdentifier' => $email,
            'password' => '1234',
        ]);
        $this->assertSame(201, $tokenResponse->getStatusCode());

        return [$userId, $this->getBody($tokenResponse)['token']];
    }

    private function createNode(string $token, string $parentPath = '/'): string
    {
        $response = $this->runPostRequest($parentPath, $token, ['type' => 'Data', 'data' => ['name' => 'on created by user']]);
        $this->assertIsCreatedResponse($response);

        return $this->getUuidFromLocation($response);
    }

    /**
     * Writes the access relation straight into the graph, as a test precondition.
     *
     * @param array<string, mixed> $properties
     */
    private function grant(string $type, string $userId, string $elementId, array $properties = []): void
    {
        $this->getCypherClient()->run(
            sprintf(
                'MATCH (u:User {id: $userId}), (e {id: $elementId}) '.
                'CREATE (u)-[r:%s {id: $relationId, created: datetime(), updated: datetime()}]->(e)%s',
                $type,
                [] === $properties ? '' : ' SET r += $properties'
            ),
            [
                'userId' => $userId,
                'elementId' => $elementId,
                'relationId' => Uuid::generate()->toString(),
                ...([] === $properties ? [] : ['properties' => $properties]),
            ]
        );
    }

    public function testReadAccessOnlyForElementsCreatedByTheUser(): void
    {
        [$userId, $userToken] = $this->registerUserWithToken();

        $notCreatedByUser = $this->createNode(self::OWNER_TOKEN);
        $parent = $this->createNode(self::OWNER_TOKEN);
        $this->grant('HAS_CREATE_ACCESS', $userId, $parent);
        $createdByUser = $this->createNode($userToken, sprintf('/%s', $parent));

        $this->assertIsCreatedBy(self::OWNER_TOKEN, $createdByUser, $userId);

        $this->grant('HAS_READ_ACCESS', $userId, $notCreatedByUser, ['onCreatedByUser' => true]);
        $this->grant('HAS_READ_ACCESS', $userId, $createdByUser, ['onCreatedByUser' => true]);

        $this->assertSame(200, $this->runGetRequest(sprintf('/%s', $createdByUser), $userToken)->getStatusCode());
        $this->assertSame(404, $this->runGetRequest(sprintf('/%s', $notCreatedByUser), $userToken)->getStatusCode());
    }
}
