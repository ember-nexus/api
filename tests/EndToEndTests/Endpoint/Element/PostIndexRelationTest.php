<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Element;

use App\Tests\EndToEndTests\BaseRequestTestCase;

class PostIndexRelationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:OZmOjZnLpek1ppUvog82KR';
    private const string START = '02IiNr0GOoKiPMLTZW4nG3aFeCuD6GdoH9wjQ';
    private const string END = '02IiNr0GOoKiPMMD6Ua61xSFAbxJbaIke8Wru';

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
