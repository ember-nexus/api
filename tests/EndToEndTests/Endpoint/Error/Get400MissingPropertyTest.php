<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Error;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class Get400MissingPropertyTest extends BaseRequestTestCase
{
    public function testGet400MissingProperty(): void
    {
        $response = $this->runGetRequest('/error/400/missing-property', null);
        $this->assertIsTextResponse($response, 200);
    }
}
