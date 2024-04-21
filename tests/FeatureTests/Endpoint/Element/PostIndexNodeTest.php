<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Endpoint\Element;

use App\Tests\FeatureTests\BaseRequestTestCase;

class PostIndexNodeTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:NMASHc1jOGZdU7pEl3j68T';

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

    public function testCreateNodeWithoutData(): void
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
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
