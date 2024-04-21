<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Endpoint\User;

use App\Tests\FeatureTests\BaseRequestTestCase;

class DeleteTokenTest extends BaseRequestTestCase
{
    private const string TOKEN_1 = 'secret-token:HWqEN8ai8eZtnfEIo4e1GI';
    private const string TOKEN_2 = 'secret-token:RIaBS3MoIoQRbu45ES4ZTP';

    public function testDeleteToken(): void
    {
        $indexResponse = $this->runGetRequest('/', self::TOKEN_1);
        $this->assertIsCollectionResponse($indexResponse);

        $response = $this->runDeleteRequest('/token', self::TOKEN_1);
        $this->assertNoContentResponse($response);

        $failingIndexResponse = $this->runGetRequest('/', self::TOKEN_1);
        $this->assertIsProblemResponse($failingIndexResponse, 401);

        $newIndexResponse = $this->runGetRequest('/', self::TOKEN_2);
        $this->assertIsCollectionResponse($newIndexResponse);

        $countBeforeDeletion = json_decode((string) $indexResponse->getBody(), true)['totalNodes'];
        $countAfterDeletion = json_decode((string) $newIndexResponse->getBody(), true)['totalNodes'];

        $this->assertSame($countBeforeDeletion, $countAfterDeletion + 1);
    }
}
