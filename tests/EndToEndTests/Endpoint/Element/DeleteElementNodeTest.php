<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Element;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class DeleteElementNodeTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:CevJS3ZkDtJcCdqEhFKqWF';
    private const string DATA = '02IiNr0GOoKiPMLmiVsBdAPtibfrpZE8uLou8';

    public function testDeleteNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $response = $this->runDeleteRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsDeletedResponse($response);
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsProblemResponse($response, 404);
    }
}
