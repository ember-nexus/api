<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\Endpoint\Element;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

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

    #[Group('command')]
    public function testCreateNodeWithoutTokenFailsWhenAnonymousUserIsMissing(): void
    {
        $this->withoutAnonymousUser(function (): void {
            $response = $this->runPostRequest('/', null, ['type' => 'Data']);
            $this->assertIsProblemResponse($response, 403);
        });
    }
}
