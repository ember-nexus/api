<?php

namespace App\tests\FeatureTests\Endpoint\Scenario03PutElement;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _E3_01_PutNodeTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:SuB0jXgi2sFAkC9su6ecr8';
    public const DATA = 'b0f77cc4-5cd1-4de6-a172-ca49a662e0a7';

    public function testPutNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $data = $this->getBody($response)['data'];
        $this->assertArrayHasKey('test', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('created', $data);
        $this->assertArrayHasKey('updated', $data);
        $this->assertArrayHasNoNullValues($data);

        $response = $this->runPutRequest(
            sprintf('/%s', self::DATA),
            self::TOKEN,
            [
                'new-property' => 'new value',
                'test' => 'e3-01',
            ]
        );
        $this->assertNoContentResponse($response);

        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $data = $this->getBody($response)['data'];
        $this->assertArrayHasKey('test', $data);
        $this->assertArrayHasKey('created', $data);
        $this->assertArrayHasKey('new-property', $data);
        $this->assertArrayHasKey('updated', $data);
        $this->assertArrayNotHasKey('name', $data);
        $this->assertArrayHasNoNullValues($data);
    }
}
