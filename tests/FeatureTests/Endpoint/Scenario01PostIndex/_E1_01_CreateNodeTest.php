<?php

namespace App\tests\FeatureTests\Endpoint\Scenario01PostIndex;

use App\Tests\FeatureTests\BaseRequestTestCase;

class _E1_01_CreateNodeTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:NMASHc1jOGZdU7pEl3j68T';

    public function testCreateNode(): void
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'some name',
                    'scenario' => 'e1-01',
                ],
            ]
        );
        $this->assertIsCreatedResponse($response);
    }

    public function testCreateNodeWithoutType(): void
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'data' => [
                    'name' => 'some name',
                    'scenario' => 'e1-01',
                ],
            ]
        );
        $this->assertIsProblemResponse($response, 400);
    }
}
