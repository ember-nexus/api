<?php

namespace App\tests\FeatureTests\Endpoint\Scenario03PutElement;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _E3_02_PutRelationTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:7Sm1i6YAFHCW2FFf5KmG3j';
    public const RELATION = '0c3898d0-e0f4-4ad1-a754-347ea0865ac5';

    public function testPutRelation(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::RELATION), self::TOKEN);
        $this->assertIsRelationResponse($response, 'RELATION');
        $data = $this->getBody($response)['data'];
        $this->assertArrayHasKey('test', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('created', $data);
        $this->assertArrayHasKey('updated', $data);
        $this->assertArrayHasNoNullValues($data);

        $response = $this->runPutRequest(
            sprintf('/%s', self::RELATION),
            self::TOKEN,
            [
                'new-property' => 'new value',
                'test' => 'e3-02',
            ]
        );
        $this->assertNoContentResponse($response);

        $response = $this->runGetRequest(sprintf('/%s', self::RELATION), self::TOKEN);
        $this->assertIsRelationResponse($response, 'RELATION');
        $data = $this->getBody($response)['data'];
        $this->assertArrayHasKey('test', $data);
        $this->assertArrayHasKey('created', $data);
        $this->assertArrayHasKey('new-property', $data);
        $this->assertArrayHasKey('updated', $data);
        $this->assertArrayNotHasKey('name', $data);
        $this->assertArrayHasNoNullValues($data);
    }
}
