<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Element;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class GetElementNodeTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:MmFndbNiEHUr87EtAl8mH4';
    private const string DATA = '02IiNr0GOoKiPMLoQ9m0JTYrpAHdxOWWB3JFv';

    public function testGetElementNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
    }
}
