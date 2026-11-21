<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Element;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class GetRelatedTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:8PTmlQm8B9QGM5Nen3U4f7';
    private const string CENTER = '02IiNr0GOoKiPMMX2V6yB0nzCo77jIp9MxLWV';

    public function testGetRelated(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/related', self::CENTER), self::TOKEN);
        $this->assertIsCollectionResponse($response, 4, 5);
    }
}
