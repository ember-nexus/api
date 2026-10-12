<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Element;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class GetIndexTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:55qfUCKq1JopVlrRCqF0Pr';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response, 3, 0);
    }
}
