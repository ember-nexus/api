<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Element;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class PostElementNodeTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:27bYlF6ZPIaGNbC6VT3V41';
    private const string ID_DATA = '02IiNr0GOoKiPMLMZVvVZNLb5GOXWQhzznNG5';
    private const string ID_USER = '02IiNr0GOoKiPMN4UD1Ie1GR6kUqzPQpQbT7B';

    public function testCreateNode(): void
    {
        $response = $this->runPostRequest(
            sprintf('/%s', self::ID_DATA),
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'child element',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);

        $childElementId = $this->getUuidFromLocation($response);

        // check owns relationship

        $response = $this->runGetRequest(
            sprintf('/%s/related', $childElementId),
            self::TOKEN
        );
        $this->assertIsCollectionResponse($response);

        $data = $this->getBody($response);

        $childOwnedId = null;
        foreach ($data['relations'] as $relation) {
            if ('OWNS' === $relation['type']) {
                $childOwnedId = $relation['id'];
                $this->assertSame(self::ID_DATA, $relation['start']);
                $this->assertSame($childElementId, $relation['end']);
                break;
            }
        }
        $this->assertNotNull($childOwnedId);

        // created-by is a property, not a relation
        foreach ($data['relations'] as $relation) {
            $this->assertNotSame('CREATED', $relation['type']);
        }
        $this->assertIsCreatedBy(self::TOKEN, $childElementId, self::ID_USER);
    }

    public function testCreateElementAsUSerChild(): void
    {
        $response = $this->runPostRequest(
            sprintf('/%s', self::ID_USER),
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'root child element',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);

        $childElementId = $this->getUuidFromLocation($response);

        // check owns relationship

        $response = $this->runGetRequest(
            sprintf('/%s/related', $childElementId),
            self::TOKEN
        );
        $this->assertIsCollectionResponse($response);

        $data = $this->getBody($response);

        $childOwnedId = null;
        foreach ($data['relations'] as $relation) {
            if ('OWNS' === $relation['type']) {
                $childOwnedId = $relation['id'];
                $this->assertSame(self::ID_USER, $relation['start']);
                $this->assertSame($childElementId, $relation['end']);
                break;
            }
        }
        $this->assertNotNull($childOwnedId);

        // created-by is a property, not a relation
        foreach ($data['relations'] as $relation) {
            $this->assertNotSame('CREATED', $relation['type']);
        }
        $this->assertIsCreatedBy(self::TOKEN, $childElementId, self::ID_USER);
    }
}
