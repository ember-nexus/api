<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Error;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class Get400BadContentTest extends BaseRequestTestCase
{
    public function testGet400BadContent(): void
    {
        $response = $this->runGetRequest('/error/400/bad-content', null);
        $this->assertIsTextResponse($response, 200);
    }
}
