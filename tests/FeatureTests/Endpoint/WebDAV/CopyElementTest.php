<?php

namespace App\tests\FeatureTests\Endpoint\WebDAV;

use App\Tests\FeatureTests\BaseRequestTestCase;

class CopyElementTest extends BaseRequestTestCase
{
    private const SOME_UUID = 'f686f998-cb92-4db2-89e5-d088e4ac34cc';

    public function testCopyElementIsNotImplemented(): void
    {
        $response = $this->runCopyRequest(sprintf('/%s', self::SOME_UUID), null);
        $this->assertIsProblemResponse($response, 501);
    }
}
