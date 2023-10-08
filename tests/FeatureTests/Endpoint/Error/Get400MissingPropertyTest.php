<?php

namespace App\tests\FeatureTests\Endpoint\Error;

use App\Tests\FeatureTests\BaseRequestTestCase;

class Get400MissingPropertyTest extends BaseRequestTestCase
{
    public function testGet400MissingProperty(): void
    {
        $response = $this->runGetRequest('/error/400/missing-property', null);
        $this->assertIsTextResponse($response, 200);
    }
}
