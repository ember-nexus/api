<?php

namespace App\tests\ExampleGenerationController\System;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class GetWellKnownSecurityTxtTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';

    public function testGetWellKnownSecurityTxtSuccess(): void
    {
        $response = $this->runGetRequest('/.well-known/security.txt', null);
        $documentationHeadersPath = 'docs/api-endpoints/system/get-well-known-security-txt/200-response-header.txt';
        $documentationBodyPath = 'docs/api-endpoints/system/get-well-known-security-txt/200-response-body.txt';
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            $documentationHeadersPath,
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            $documentationBodyPath,
            $response,
            false,
            [
                'Expires',
            ]
        );
    }
}
