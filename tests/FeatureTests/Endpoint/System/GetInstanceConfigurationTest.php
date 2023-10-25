<?php

namespace App\tests\FeatureTests\Endpoint\System;

use App\Tests\FeatureTests\BaseRequestTestCase;

class GetInstanceConfigurationTest extends BaseRequestTestCase
{
    public function testGetInstanceConfiguration(): void
    {
        $response = $this->runGetRequest('/instance-configuration', null);

        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('version', $body);
        $this->assertArrayHasKey('pageSize', $body);
        $this->assertArrayHasKey('register', $body);
    }
}
