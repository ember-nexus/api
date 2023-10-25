<?php

namespace App\tests\FeatureTests\Endpoint\Error;

use App\Tests\FeatureTests\BaseRequestTestCase;

class Get501NotImplementedTest extends BaseRequestTestCase
{
    public function testGet501NotImplemented(): void
    {
        $response = $this->runGetRequest('/error/501/not-implemented', null);
        $this->assertIsTextResponse($response, 200);
    }
}
