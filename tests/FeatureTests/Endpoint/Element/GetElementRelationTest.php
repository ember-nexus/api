<?php

namespace App\tests\FeatureTests\Endpoint\Element;

use App\Tests\FeatureTests\BaseRequestTestCase;

class GetElementRelationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1cgn8kiSLVMTTZG6DNNII6';
    private const string DATA = '8d5a0747-58a9-4c05-9659-8980be4ed4f0';

    public function testGetElementRelation(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsRelationResponse($response, 'RELATION');
    }
}
