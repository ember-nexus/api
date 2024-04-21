<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Endpoint\Element;

use App\Tests\FeatureTests\BaseRequestTestCase;

class GetChildrenTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:OTYRavhRMtCilv30hiX617';
    private const string PARENT_UUID = '1219e253-b147-4fa6-a567-5a79db2d2eb3';

    public function testGetChildren(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/children', self::PARENT_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 3, 3);
    }
}
