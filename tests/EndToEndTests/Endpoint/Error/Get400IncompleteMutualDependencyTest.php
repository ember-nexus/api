<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Error;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class Get400IncompleteMutualDependencyTest extends BaseRequestTestCase
{
    public function testGet400IncompleteMutualDependency(): void
    {
        $response = $this->runGetRequest('/error/400/incomplete-mutual-dependency', null);
        $this->assertIsTextResponse($response, 200);
    }
}
