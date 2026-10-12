<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Error;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class Get403ForbiddenTest extends BaseRequestTestCase
{
    public function testGet403Forbidden(): void
    {
        $response = $this->runGetRequest('/error/403/forbidden', null);
        $this->assertIsTextResponse($response, 200);
    }
}
