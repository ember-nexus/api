<?php

namespace App\tests\FeatureTests\Endpoint\Element;

use App\Tests\FeatureTests\BaseRequestTestCase;

class DeleteElementNodeTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:CevJS3ZkDtJcCdqEhFKqWF';
    private const string DATA = '55cce573-1377-4781-be16-8b81587aca10';

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
