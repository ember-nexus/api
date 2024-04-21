<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Security;

use App\Tests\FeatureTests\BaseRequestTestCase;

class DeleteTokenTest extends BaseRequestTestCase
{
    private const string TOKEN_1 = 'secret-token:MvsgHPbJY6LhtTbm9jnNXs';
    private const string TOKEN_2 = 'secret-token:5nm7j0FE6sPuHqRgjvK40U';
    private const string TOKEN_2_UUID = '2ddfc37a-f763-41a6-8826-084aec2a283f';
    private const string TOKEN_3 = 'secret-token:KhPjldUIB3YYMU44CUTJpC';

    public function testDeleteTokenThroughDeleteTokenEndpoint(): void
    {
        $indexResponse = $this->runGetRequest('/', self::TOKEN_1);
        $this->assertIsCollectionResponse($indexResponse);

        $response = $this->runDeleteRequest('/token', self::TOKEN_1);
        $this->assertNoContentResponse($response);

        $failingIndexResponse = $this->runGetRequest('/', self::TOKEN_1);
        $this->assertIsProblemResponse($failingIndexResponse, 401);

        $newIndexResponse = $this->runGetRequest('/', self::TOKEN_3);
        $this->assertIsCollectionResponse($newIndexResponse);

        $countBeforeDeletion = json_decode((string) $indexResponse->getBody(), true)['totalNodes'];
        $countAfterDeletion = json_decode((string) $newIndexResponse->getBody(), true)['totalNodes'];

        $this->assertSame($countBeforeDeletion, $countAfterDeletion + 1);
    }

    public function testDeleteTokenThroughDeleteElementEndpoint(): void
    {
        $indexResponse = $this->runGetRequest('/', self::TOKEN_2);
        $this->assertIsCollectionResponse($indexResponse);

        $indexResponse = $this->runGetRequest('/', self::TOKEN_3);
        $this->assertIsCollectionResponse($indexResponse);

        $response = $this->runDeleteRequest(sprintf('/%s', self::TOKEN_2_UUID), self::TOKEN_3);
        $this->assertNoContentResponse($response);

        $failingIndexResponse = $this->runGetRequest('/', self::TOKEN_2);
        $this->assertIsProblemResponse($failingIndexResponse, 401);

        $newIndexResponse = $this->runGetRequest('/', self::TOKEN_3);
        $this->assertIsCollectionResponse($newIndexResponse);

        $countBeforeDeletion = json_decode((string) $indexResponse->getBody(), true)['totalNodes'];
        $countAfterDeletion = json_decode((string) $newIndexResponse->getBody(), true)['totalNodes'];

        $this->assertSame($countBeforeDeletion, $countAfterDeletion + 1);
    }
}
