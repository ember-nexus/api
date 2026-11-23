<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Element;

use App\Tests\EndToEndTests\BaseRequestTestCase;

/**
 * `createdBy` and `updatedBy` are server managed properties holding the id of the acting user.
 */
class ElementActorPropertiesTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:27bYlF6ZPIaGNbC6VT3V41';
    private const string ID_USER = '02IiNr0GOoKiPMN4UD1Ie1GR6kUqzPQpQbT7B';

    private function createNode(): string
    {
        $response = $this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'actor properties'],
        ]);
        $this->assertIsCreatedResponse($response);

        return $this->getUuidFromLocation($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function getData(string $id): array
    {
        $response = $this->runGetRequest(sprintf('/%s', $id), self::TOKEN);
        $this->assertSame(200, $response->getStatusCode());

        return $this->getBody($response)['data'];
    }

    public function testCreatedAndUpdatedByAreSetOnCreate(): void
    {
        $data = $this->getData($this->createNode());
        $this->assertSame(self::ID_USER, $data['createdBy']);
        $this->assertSame(self::ID_USER, $data['updatedBy']);
    }

    public function testRelationGetsCreatedAndUpdatedBy(): void
    {
        $startId = $this->createNode();
        $endId = $this->createNode();
        $response = $this->runPostRequest('/', self::TOKEN, [
            'type' => 'LINKS_TO',
            'start' => $startId,
            'end' => $endId,
            'data' => [],
        ]);
        $this->assertIsCreatedResponse($response);
        $data = $this->getData($this->getUuidFromLocation($response));
        $this->assertSame(self::ID_USER, $data['createdBy']);
        $this->assertSame(self::ID_USER, $data['updatedBy']);
    }

    public function testCreatedByAndUpdatedByCanNotBeSetOnCreate(): void
    {
        foreach (['createdBy', 'updatedBy'] as $property) {
            $response = $this->runPostRequest('/', self::TOKEN, [
                'type' => 'Data',
                'data' => [$property => self::ID_USER],
            ]);
            $this->assertIsProblemResponse($response, 400);
        }
    }

    public function testCreatedByAndUpdatedByCanNotBeChanged(): void
    {
        $id = $this->createNode();
        foreach (['createdBy', 'updatedBy'] as $property) {
            $this->assertIsProblemResponse(
                $this->runPatchRequest(sprintf('/%s', $id), self::TOKEN, [$property => self::ID_USER]),
                400
            );
            $this->assertIsProblemResponse(
                $this->runPutRequest(sprintf('/%s', $id), self::TOKEN, [$property => self::ID_USER]),
                400
            );
        }
        $data = $this->getData($id);
        $this->assertSame(self::ID_USER, $data['createdBy']);
    }

    public function testPatchAndPutKeepCreatedByAndRefreshUpdatedBy(): void
    {
        $id = $this->createNode();

        $response = $this->runPatchRequest(sprintf('/%s', $id), self::TOKEN, ['name' => 'patched']);
        $this->assertSame(204, $response->getStatusCode());
        $data = $this->getData($id);
        $this->assertSame(self::ID_USER, $data['createdBy']);
        $this->assertSame(self::ID_USER, $data['updatedBy']);

        $response = $this->runPutRequest(sprintf('/%s', $id), self::TOKEN, ['name' => 'replaced']);
        $this->assertSame(204, $response->getStatusCode());
        $data = $this->getData($id);
        $this->assertSame('replaced', $data['name']);
        $this->assertSame(self::ID_USER, $data['createdBy']);
        $this->assertSame(self::ID_USER, $data['updatedBy']);
    }
}
