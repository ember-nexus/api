<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Element;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class GetChildrenTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:OTYRavhRMtCilv30hiX617';
    private const string PARENT_UUID = '02IiNr0GOoKiPMLFkto3DYySDasRtisTc2fnu';

    public function testGetChildren(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/children', self::PARENT_UUID), self::TOKEN);
        $this->assertIsCollectionResponse($response, 3, 3);
    }
}
