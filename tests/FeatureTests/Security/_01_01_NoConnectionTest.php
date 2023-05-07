<?php

namespace App\Tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _01_01_NoConnectionTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:451NiderFjTRW7TpoLoSWA';

    public const DATA = 'b1b253a6-d9a5-45b5-b7a9-1be29ced4df6';

    public function testGetIndex(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);
        $this->assertIsCollectionResponse($response);
    }

    public function testGetForbiddenNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
