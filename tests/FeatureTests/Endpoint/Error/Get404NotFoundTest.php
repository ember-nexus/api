<?php

namespace App\tests\FeatureTests\Endpoint\Error;

use App\Tests\FeatureTests\BaseRequestTestCase;

class Get404NotFoundTest extends BaseRequestTestCase
{
    public function testGet404NotFound(): void
    {
        $response = $this->runGetRequest('/error/404/not-found', null);
        $this->assertIsTextResponse($response, 200);
    }
}
