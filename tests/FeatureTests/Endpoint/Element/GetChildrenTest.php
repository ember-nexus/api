<?php

namespace App\tests\FeatureTests\Endpoint\Element;

use App\Tests\FeatureTests\BaseRequestTestCase;

class GetChildrenTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:OTYRavhRMtCilv30hiX617';
    public const PARENT = '1219e253-b147-4fa6-a567-5a79db2d2eb3';

    public function testGetChildren(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/children', self::PARENT), self::TOKEN);
        $this->assertIsCollectionResponse($response, 3, 3);
    }
}
