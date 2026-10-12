<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Error;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class Get401UnauthorizedTest extends BaseRequestTestCase
{
    public function testGet401Unauthorized(): void
    {
        $response = $this->runGetRequest('/error/401/unauthorized', null);
        $this->assertIsTextResponse($response, 200);
    }
}
