<?php

namespace App\tests\FeatureTests\Endpoint\Error;

use App\Tests\FeatureTests\BaseRequestTestCase;

class Get401UnauthorizedTest extends BaseRequestTestCase
{
    public function testGet401Unauthorized(): void
    {
        $response = $this->runGetRequest('/error/401/unauthorized', null);
        $this->assertIsTextResponse($response, 200);
    }
}
