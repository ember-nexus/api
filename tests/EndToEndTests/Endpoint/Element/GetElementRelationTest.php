<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Element;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class GetElementRelationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1cgn8kiSLVMTTZG6DNNII6';
    private const string DATA = '02IiNr0GOoKiPMMDm0k0WQYZQyPLA4iuBcRU3';

    public function testGetElementRelation(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsRelationResponse($response, 'RELATION');
    }
}
