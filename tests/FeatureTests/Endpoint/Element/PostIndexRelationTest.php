<?php

namespace App\tests\FeatureTests\Endpoint\Element;

use App\Tests\FeatureTests\BaseRequestTestCase;

class PostIndexRelationTest extends BaseRequestTestCase
{
    public const TOKEN = 'secret-token:OZmOjZnLpek1ppUvog82KR';
    public const START = '2e709799-b84b-487f-837b-9ba92b991614';
    public const END = '8bf94f2b-898e-4e50-9657-6a9a0736a1be';

    public function testCreateRelation(): void
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'RELATION',
                'data' => [
                    'name' => 'some name',
                    'scenario' => 'e1-02',
                ],
                'start' => self::START,
                'end' => self::END,
            ]
        );
        $this->assertIsCreatedResponse($response);
    }

    public function testCreateRelationWithoutData(): void
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'RELATION',
                'start' => self::START,
                'end' => self::END,
            ]
        );
        $this->assertIsCreatedResponse($response);
    }

    public function testCreateRelationWithoutType(): void
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'data' => [
                    'name' => 'some name',
                    'scenario' => 'e1-02',
                ],
                'start' => self::START,
                'end' => self::END,
            ]
        );
        $this->assertIsProblemResponse($response, 400);
    }

    public function testCreateRelationWithoutStart(): void
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'RELATION',
                'data' => [
                    'name' => 'some name',
                    'scenario' => 'e1-02',
                ],
                'end' => self::END,
            ]
        );
        $this->assertIsProblemResponse($response, 400);
    }

    public function testCreateRelationWithoutEnd(): void
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'RELATION',
                'data' => [
                    'name' => 'some name',
                    'scenario' => 'e1-02',
                ],
                'start' => self::START,
            ]
        );
        $this->assertIsProblemResponse($response, 400);
    }
}
