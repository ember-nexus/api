<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\System;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class GetWellKnownSecurityTxtTest extends BaseRequestTestCase
{
    public function testGetWellKnownSecurityTxt(): void
    {
        $response = $this->runGetRequest('/.well-known/security.txt', null);
        $this->assertIsTextResponse($response, 200);
    }
}
