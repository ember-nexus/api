<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Error;

use App\Tests\FeatureTests\BaseRequestTestCase;

class Get400ReservedTypeTest extends BaseRequestTestCase
{
    public function testGet400ReservedType(): void
    {
        $response = $this->runGetRequest('/error/400/reserved-type', null);
        $this->assertIsTextResponse($response, 200);
    }
}
