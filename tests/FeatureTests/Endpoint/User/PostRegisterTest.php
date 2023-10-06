<?php

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
                'data' => [
                    'email' => 'user1@register.user.endpoint.localhost.dev',
                ],
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
                'data' => [
                    'email' => 'user2@register.user.endpoint.localhost.dev',
                ],
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
                'data' => [
                    'email' => 'user3@register.user.endpoint.localhost.dev',
                ],
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
                'data' => [
                    'email' => 'user4@register.user.endpoint.localhost.dev',
                ],
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
                'data' => [
                ],
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
                'data' => [
                    'email' => 'user5@register.user.endpoint.localhost.dev',
                ],
            ]
        );

        $this->assertIsCreatedResponse($response1);

        $response2 = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'User',
                'password' => '1234',
                'data' => [
                    'email' => 'user5@register.user.endpoint.localhost.dev',
                ],
            ]
        );

        $this->assertIsProblemResponse($response2, 400);
    }
}
