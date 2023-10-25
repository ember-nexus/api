<?php

namespace App\tests\FeatureTests\Endpoint\Error;

use App\Tests\FeatureTests\BaseRequestTestCase;

class Get403ForbiddenTest extends BaseRequestTestCase
{
    public function testGet403Forbidden(): void
    {
        $response = $this->runGetRequest('/error/403/forbidden', null);
        $this->assertIsTextResponse($response, 200);
    }
}
