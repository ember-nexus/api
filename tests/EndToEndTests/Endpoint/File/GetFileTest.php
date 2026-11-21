<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\File;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('test')]
class GetFileTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const string NODE_WITH_FILE_UUID = '02IiNr0GOoKiPMLEezecUZIcqABdykANm0j0F';
    private const string NODE_WITHOUT_FILE_UUID = '02IiNr0GOoKiPMMY6RzeYaPpTMxaHXVGh8k0X';

    public function testGetFileOnNodeWithFile(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::NODE_WITH_FILE_UUID), self::TOKEN);
        $this->assertIsBinaryStreamResponse($response, 'image/jpeg');
    }

    public function testGetFileOnNodeWithoutFile(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::NODE_WITHOUT_FILE_UUID), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }

    public function testGetFileOnRelationWithFile(): void
    {
        $startNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'get-file-on-relation-with-file-start',
                ],
            ]
        );
        $startNodeId = $this->getUuidFromLocation($startNode);

        $endNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'get-file-on-relation-with-file-end',
                ],
            ]
        );
        $endNodeId = $this->getUuidFromLocation($endNode);

        $relation = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'start' => $startNodeId,
                'end' => $endNodeId,
                'data' => [
                    'name' => 'get-file-on-relation-with-file',
                ],
            ]
        );
        $relationId = $this->getUuidFromLocation($relation);

        $file = \Safe\fopen(__DIR__.'/../../Asset/cherry-blossoms.jpg', 'r');
        $uploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $file,
            self::TOKEN
        );
        $this->assertIsCreatedResponse($uploadResponse, false);

        $response = $this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN);
        $this->assertIsBinaryStreamResponse($response, 'image/jpeg');
    }

    public function testGetFileOnRelationWithoutFile(): void
    {
        $startNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'get-file-on-relation-without-file-start',
                ],
            ]
        );
        $startNodeId = $this->getUuidFromLocation($startNode);

        $endNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'get-file-on-relation-without-file-end',
                ],
            ]
        );
        $endNodeId = $this->getUuidFromLocation($endNode);

        $relation = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'start' => $startNodeId,
                'end' => $endNodeId,
                'data' => [
                    'name' => 'get-file-on-relation-without-file',
                ],
            ]
        );
        $relationId = $this->getUuidFromLocation($relation);

        $response = $this->runGetRequest(sprintf('/%s/file', $relationId), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
