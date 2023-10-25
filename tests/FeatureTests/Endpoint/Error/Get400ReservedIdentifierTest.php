<?php

namespace App\tests\FeatureTests\Endpoint\Error;

use App\Tests\FeatureTests\BaseRequestTestCase;

class Get400ReservedIdentifierTest extends BaseRequestTestCase
{
    public function testGet400ReservedIdentifier(): void
    {
        $response = $this->runGetRequest('/error/400/reserved-identifier', null);
        $this->assertIsTextResponse($response, 200);
    }
}
