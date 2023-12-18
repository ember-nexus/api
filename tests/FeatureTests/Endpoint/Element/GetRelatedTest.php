<?php

namespace App\tests\FeatureTests\Endpoint\Element;

use App\Tests\FeatureTests\BaseRequestTestCase;

class GetRelatedTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:8PTmlQm8B9QGM5Nen3U4f7';
    private const string CENTER = 'b4eee6fe-c177-4454-b809-ef2766658fd5';

    public function testGetRelated(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/related', self::CENTER), self::TOKEN);
        $this->assertIsCollectionResponse($response, 4, 5);
    }
}
