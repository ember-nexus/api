<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\General\Edgecases;

use App\Tests\FeatureTests\BaseRequestTestCase;

class NoRouteTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:FcXR4LsliYfWkYFKhTVovA';

    public function testUnknownRouteReturnsCustom404Problem(): void
    {
        $response = $this->runGetRequest('/this-route-does-not-exist', self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
