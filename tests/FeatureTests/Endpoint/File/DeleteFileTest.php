<?php

namespace App\tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

class DeleteFileTest extends BaseRequestTestCase
{
    private const string SOME_UUID = 'f686f998-cb92-4db2-89e5-d088e4ac34cc';

    public function testDeleteFileIsNotImplemented(): void
    {
        $response = $this->runDeleteRequest(sprintf('/%s/file', self::SOME_UUID), null);
        $this->assertIsProblemResponse($response, 501);
    }
}
