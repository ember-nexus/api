<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

class PutFileTest extends BaseRequestTestCase
{
    private const string SOME_UUID = 'f686f998-cb92-4db2-89e5-d088e4ac34cc';

    public function testPutFileIsNotImplemented(): void
    {
        $response = $this->runPutRequest(
            sprintf('/%s/file', self::SOME_UUID),
            null,
            []
        );
        $this->assertIsProblemResponse($response, 501);
    }
}
