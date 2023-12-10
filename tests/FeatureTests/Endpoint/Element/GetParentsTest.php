<?php

namespace App\tests\FeatureTests\Endpoint\Element;

use App\Tests\FeatureTests\BaseRequestTestCase;

class GetParentsTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:TVg2TMLeeK6AJMT4QhNsjA';
    public const CHILD = '65ad4ffe-17b3-4258-894f-561d64abd2db';

    public function testGetParents(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/parents', self::CHILD), self::TOKEN);
        $this->assertIsCollectionResponse($response, 1, 1);
    }
}
