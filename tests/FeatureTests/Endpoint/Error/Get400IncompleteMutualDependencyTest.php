<?php

namespace App\tests\FeatureTests\Endpoint\Error;

use App\Tests\FeatureTests\BaseRequestTestCase;

class Get400IncompleteMutualDependencyTest extends BaseRequestTestCase
{
    public function testGet400IncompleteMutualDependency(): void
    {
        $response = $this->runGetRequest('/error/400/incomplete-mutual-dependency', null);
        $this->assertIsTextResponse($response, 200);
    }
}
