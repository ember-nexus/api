<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Endpoint\Element;

use App\Tests\FeatureTests\BaseRequestTestCase;

class PatchElementRelationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:S26Pn61Imv52pWOJ9GuXET';
    private const string RELATION = 'd639767c-486c-4714-aa61-8d28ec6be338';

    public function testPutRelation(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::RELATION), self::TOKEN);
        $this->assertIsRelationResponse($response, 'RELATION');
        $data = $this->getBody($response)['data'];
        $this->assertArrayHasKey('scenario', $data);
        $this->assertArrayHasKey('created', $data);
        $this->assertArrayHasKey('updated', $data);
        $this->assertArrayHasNoNullValues($data);

        $response = $this->runPatchRequest(
            sprintf('/%s', self::RELATION),
            self::TOKEN,
            [
                'new-property' => 'new value',
            ]
        );
        $this->assertNoContentResponse($response);

        $response = $this->runGetRequest(sprintf('/%s', self::RELATION), self::TOKEN);
        $this->assertIsRelationResponse($response, 'RELATION');
        $data = $this->getBody($response)['data'];
        $this->assertArrayHasKey('scenario', $data);
        $this->assertArrayHasKey('created', $data);
        $this->assertArrayHasKey('new-property', $data);
        $this->assertArrayHasKey('updated', $data);
        $this->assertArrayHasNoNullValues($data);
    }
}
