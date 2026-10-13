<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDifferentConfiguration2\User;

use App\Tests\EndToEndTests\BaseRequestTestCase;

/**
 * This instance is configured with register.uniqueIdentifierRegex set to '/^[0-9]+$/', proving that a
 * non-default regex is actually enforced, not just the built-in default.
 */
class PostRegisterTest extends BaseRequestTestCase
{
    public function testPostRegisterSucceedsWithIdentifierMatchingConfiguredRegex(): void
    {
        $response = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'User',
                'password' => '1234',
                'uniqueUserIdentifier' => '1000000001',
            ]
        );

        $this->assertIsCreatedResponse($response);
    }

    public function testPostRegisterFailsWithIdentifierNotMatchingConfiguredRegex(): void
    {
        $response = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'User',
                'password' => '1234',
                'uniqueUserIdentifier' => 'not-a-number',
            ]
        );

        $this->assertIsProblemResponse($response, 400);
    }
}
