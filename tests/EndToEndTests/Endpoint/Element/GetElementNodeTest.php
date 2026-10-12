<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Element;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class GetElementNodeTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:MmFndbNiEHUr87EtAl8mH4';
    private const string DATA = '59493df1-736f-4860-b4a8-a7ebb2bcd96e';

    public function testGetElementNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }
}
