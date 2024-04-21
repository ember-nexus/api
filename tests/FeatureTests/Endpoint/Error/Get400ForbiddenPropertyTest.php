<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Endpoint\Error;

use App\Tests\FeatureTests\BaseRequestTestCase;

class Get400ForbiddenPropertyTest extends BaseRequestTestCase
{
    public function testGet400ForbiddenProperty(): void
    {
        $response = $this->runGetRequest('/error/400/forbidden-property', null);
        $this->assertIsTextResponse($response, 200);
    }
}
