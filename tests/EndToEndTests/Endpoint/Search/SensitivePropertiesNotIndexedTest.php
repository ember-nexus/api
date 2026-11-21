<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Search;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use Elastic\Elasticsearch\ClientBuilder as ElasticsearchClientBuilder;
use Elastic\Elasticsearch\Exception\ClientResponseException;

/**
 * Password and token hashes are required for authentication, but must never be searchable or highlightable. They are
 * therefore not allowed to reach Elasticsearch, while all other properties of the same elements still are.
 *
 * The indexed documents are read directly from Elasticsearch, as users usually have no search access to their own
 * token nodes, and a document-level check does not depend on the access rules of the search endpoint.
 */
class SensitivePropertiesNotIndexedTest extends BaseRequestTestCase
{
    private const int INDEXING_TIMEOUT_IN_SECONDS = 15;

    /**
     * @return array{userId: string, token: string, tokenId: string}
     */
    private function registerUserWithToken(): array
    {
        $email = sprintf('%s@sensitive-properties.search.localhost.dev', bin2hex(random_bytes(6)));
        $password = '1234';

        $registerResponse = $this->runPostRequest('/register', null, [
            'type' => 'User',
            'password' => $password,
            'uniqueUserIdentifier' => $email,
        ]);
        $this->assertIsCreatedResponse($registerResponse);
        $userId = $this->getUuidFromLocation($registerResponse);

        $tokenResponse = $this->runPostRequest('/token', null, [
            'type' => 'Token',
            'uniqueUserIdentifier' => $email,
            'password' => $password,
        ]);
        $this->assertSame(201, $tokenResponse->getStatusCode());
        $token = $this->getBody($tokenResponse)['token'];

        $tokenNodeResponse = $this->runGetRequest('/token', $token);
        $this->assertIsNodeResponse($tokenNodeResponse, 'Token');

        return [
            'userId' => $userId,
            'token' => $token,
            'tokenId' => $this->getBody($tokenNodeResponse)['id'],
        ];
    }

    /**
     * Elements are indexed asynchronously of the request, therefore the document is polled until it exists.
     *
     * @return array<string, mixed>
     */
    private function getIndexedDocument(string $index, string $id): array
    {
        $client = ElasticsearchClientBuilder::create()->setHosts([$_ENV['ELASTIC_AUTH']])->build();

        $deadline = time() + self::INDEXING_TIMEOUT_IN_SECONDS;
        do {
            try {
                $response = $client->get(['index' => $index, 'id' => $id]);
                /** @var array{_source: array<string, mixed>} $document */
                $document = $response->asArray();

                return $document['_source'];
            } catch (ClientResponseException $exception) {
                if (404 !== $exception->getCode()) {
                    throw $exception;
                }
            }
            usleep(250_000);
        } while (time() < $deadline);

        $this->fail(sprintf("Document '%s' in index '%s' was not indexed within %d seconds.", $id, $index, self::INDEXING_TIMEOUT_IN_SECONDS));
    }

    public function testPasswordHashOfUserIsNotIndexed(): void
    {
        $registration = $this->registerUserWithToken();

        $document = $this->getIndexedDocument('node_user', $registration['userId']);

        // positive control: other properties of the user are still indexed
        $this->assertArrayHasKey('created', $document);
        $this->assertArrayNotHasKey('_passwordHash', $document);
    }

    public function testHashOfTokenIsNotIndexed(): void
    {
        $registration = $this->registerUserWithToken();

        $document = $this->getIndexedDocument('node_token', $registration['tokenId']);

        // positive control: other properties of the token are still indexed
        $this->assertArrayHasKey('created', $document);
        $this->assertArrayHasKey('state', $document);
        $this->assertArrayNotHasKey('hash', $document);
        $this->assertArrayNotHasKey('_tokenHash', $document);
    }

    public function testAuthenticationStillWorksWithoutIndexedHash(): void
    {
        // authentication relies on the hash, which stays in the graph database
        $registration = $this->registerUserWithToken();

        $this->assertIsNodeResponse($this->runGetRequest('/token', $registration['token']), 'Token');
        $this->assertIsNodeResponse($this->runGetRequest(sprintf('/%s', $registration['userId']), $registration['token']), 'User');
    }
}
