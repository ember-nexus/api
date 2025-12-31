<?php

declare(strict_types=1);

namespace App\tests\ExampleGenerationControllerWithDifferentConfiguration\User;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class PostRegisterTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testRegisterError403(): void
    {
        $response = $this->runPostRequest(
            '/register',
            null,
            [
                'type' => 'User',
                'password' => '1234',
                'uniqueUserIdentifier' => 'test2@localhost.dev',
            ]
        );
        $this->assertIsProblemResponse($response, 403);
        $documentationHeadersPath = 'docs/api-endpoints/user/post-register/403-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/user/post-register/403-response-body.json';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response
        );
    }
}
