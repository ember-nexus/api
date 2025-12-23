<?php

declare(strict_types=1);

namespace App\tests\FeatureTests\Endpoint\User;

use App\Tests\FeatureTests\BaseRequestTestCase;

class PostRegisterTest extends BaseRequestTestCase
{
    public function testPostRegister(): void
    {
        $response = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'User',
                'password' => '1234',
                'uniqueUserIdentifier' => 'user1@register.user.endpoint.localhost.dev',
            ]
        );

        $this->assertIsCreatedResponse($response);
    }

    public function testPostRegisterWithoutType(): void
    {
        $response = $this->runPostRequest(
            '/register',
            null,
            [
                'password' => '1234',
                'uniqueUserIdentifier' => 'user2@register.user.endpoint.localhost.dev',
            ]
        );

        $this->assertIsProblemResponse($response, 400);
    }

    public function testPostRegisterWithWrongType(): void
    {
        $response = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'NotAUser',
                'password' => '1234',
                'uniqueUserIdentifier' => 'user3@register.user.endpoint.localhost.dev',
            ]
        );

        $this->assertIsProblemResponse($response, 400);
    }

    public function testPostRegisterWithNoPassword(): void
    {
        $response = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'User',
                'uniqueUserIdentifier' => 'user4@register.user.endpoint.localhost.dev',
            ]
        );

        $this->assertIsProblemResponse($response, 400);
    }

    public function testPostRegisterWithNoEmail(): void
    {
        $response = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'User',
                'password' => '1234',
            ]
        );

        $this->assertIsProblemResponse($response, 400);
    }

    public function testPostRegisterFailsForDuplicateEmail(): void
    {
        $response1 = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'User',
                'password' => '1234',
                'uniqueUserIdentifier' => 'user5@register.user.endpoint.localhost.dev',
            ]
        );

        $this->assertIsCreatedResponse($response1);

        $response2 = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'User',
                'password' => '1234',
                'uniqueUserIdentifier' => 'user5@register.user.endpoint.localhost.dev',
            ]
        );

        $this->assertIsProblemResponse($response2, 400);
    }

    public function testPostRegisterCanTriggerNormalizationExceptions(): void
    {
        $response = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'User',
                'password' => '1234',
                'uniqueUserIdentifier' => 'user6@register.user.endpoint.localhost.dev',
                'data' => [
                    '_passwordHash' => 'The key "_passwordHash" can not be manually defined.',
                ],
            ]
        );

        $this->assertIsProblemResponse($response, 400);
    }
}
