<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Element;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class GetParentsTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:TVg2TMLeeK6AJMT4QhNsjA';
    private const string CHILD = '02IiNr0GOoKiPMLuRo7903qQINXcFqdP5hcJU';

    public function testGetParents(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/parents', self::CHILD), self::TOKEN);
        $this->assertIsCollectionResponse($response, 1, 1);
    }
}
