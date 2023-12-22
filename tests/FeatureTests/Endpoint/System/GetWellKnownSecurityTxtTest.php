<?php

namespace App\tests\FeatureTests\Endpoint\System;

use App\Tests\FeatureTests\BaseRequestTestCase;

class GetWellKnownSecurityTxtTest extends BaseRequestTestCase
{
    public function testGetWellKnownSecurityTxt(): void
    {
        $response = $this->runGetRequest('/.well-known/security.txt', null);
        $this->assertIsTextResponse($response, 200);
    }
}
