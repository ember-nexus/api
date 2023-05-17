<?php

namespace App\tests\FeatureTests\Endpoint\Scenario02DeleteElement;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _E2_01_DeleteNodeTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:CevJS3ZkDtJcCdqEhFKqWF';
    public const DATA = '55cce573-1377-4781-be16-8b81587aca10';

    public function testDeleteNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runDeleteRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsDeletedResponse($response);
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
