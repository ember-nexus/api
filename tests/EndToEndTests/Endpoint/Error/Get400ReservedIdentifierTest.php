<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Error;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class Get400ReservedIdentifierTest extends BaseRequestTestCase
{
    public function testGet400ReservedIdentifier(): void
    {
        $response = $this->runGetRequest('/error/400/reserved-identifier', null);
        $this->assertIsTextResponse($response, 200);
    }
}
