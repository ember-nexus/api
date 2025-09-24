<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Endpoint\Element;

use App\Tests\FeatureTests\BaseRequestTestCase;

class PostElementNodeTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:27bYlF6ZPIaGNbC6VT3V41';
    private const string ID_DATA = '2010c776-c739-4909-995b-b08e171a268a';
    private const string ID_USER = 'f994b541-23d6-4065-b992-14548f3e3345';

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

        $userCreatedId = null;
        foreach ($data['relations'] as $relation) {
            if ('CREATED' === $relation['type']) {
                $userCreatedId = $relation['id'];
                $this->assertSame(self::ID_USER, $relation['start']);
                $this->assertSame($childElementId, $relation['end']);
                break;
            }
        }
        $this->assertNotNull($userCreatedId);
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

        $userCreatedId = null;
        foreach ($data['relations'] as $relation) {
            if ('CREATED' === $relation['type']) {
                $userCreatedId = $relation['id'];
                $this->assertSame(self::ID_USER, $relation['start']);
                $this->assertSame($childElementId, $relation['end']);
                break;
            }
        }
        $this->assertNotNull($userCreatedId);
    }
}
