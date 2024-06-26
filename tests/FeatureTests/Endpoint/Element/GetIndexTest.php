<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Endpoint\Element;

use App\Tests\FeatureTests\BaseRequestTestCase;

class GetIndexTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:55qfUCKq1JopVlrRCqF0Pr';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response, 3, 0);
    }
}
