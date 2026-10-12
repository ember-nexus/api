<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Error;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class Get404NotFoundTest extends BaseRequestTestCase
{
    public function testGet404NotFound(): void
    {
        $response = $this->runGetRequest('/error/404/not-found', null);
        $this->assertIsTextResponse($response, 200);
    }
}
