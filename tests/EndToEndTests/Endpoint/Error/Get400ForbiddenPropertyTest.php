<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Error;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class Get400ForbiddenPropertyTest extends BaseRequestTestCase
{
    public function testGet400ForbiddenProperty(): void
    {
        $response = $this->runGetRequest('/error/400/forbidden-property', null);
        $this->assertIsTextResponse($response, 200);
    }
}
