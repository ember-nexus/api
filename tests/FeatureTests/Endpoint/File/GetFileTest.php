<?php

namespace App\tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

class GetFileTest extends BaseRequestTestCase
{
    private const SOME_UUID = 'f686f998-cb92-4db2-89e5-d088e4ac34cc';

    public function testGetFileIsNotImplemented(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::SOME_UUID), null);
        $this->assertIsProblemResponse($response, 501);
    }
}
