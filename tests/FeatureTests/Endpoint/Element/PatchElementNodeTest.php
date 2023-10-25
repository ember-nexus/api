<?php

namespace App\tests\FeatureTests\Endpoint\Element;

use App\Tests\FeatureTests\BaseRequestTestCase;

class PatchElementNodeTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:PsN3SfXuql92IjEUkUYZFN';
    public const DATA = 'd77ca32c-8cf2-4ed9-868e-27ae122e9ea1';

    public function testPutNode(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $data = $this->getBody($response)['data'];
        $this->assertArrayHasKey('scenario', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('created', $data);
        $this->assertArrayHasKey('updated', $data);
        $this->assertArrayHasNoNullValues($data);

        $response = $this->runPatchRequest(
            sprintf('/%s', self::DATA),
            self::TOKEN,
            [
                'new-property' => 'new value',
            ]
        );
        $this->assertNoContentResponse($response);

        $response = $this->runGetRequest(sprintf('/%s', self::DATA), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Data');
        $data = $this->getBody($response)['data'];
        $this->assertArrayHasKey('scenario', $data);
        $this->assertArrayHasKey('created', $data);
        $this->assertArrayHasKey('new-property', $data);
        $this->assertArrayHasKey('updated', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasNoNullValues($data);
    }
}
