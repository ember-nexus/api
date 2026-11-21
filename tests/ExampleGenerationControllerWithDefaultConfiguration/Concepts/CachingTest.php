<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDefaultConfiguration\Concepts;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

class CachingTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';
    private const string NODE_UUID = '02IiNr0GOoKiPMM1keIQn6X4HCRG6E9xAcDJ9';

    public function testInitialRequest(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::NODE_UUID), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Comment');
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            'tests/Recordings/concepts/caching/initial-request-response-header.txt',
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            'tests/Recordings/concepts/caching/initial-request-response-body.json',
            $response,
            true,
            [
                'created',
                'updated',
            ]
        );
    }

    public function testNotModifiedRequest(): void
    {
        $initialResponse = $this->runGetRequest(sprintf('/%s', self::NODE_UUID), self::TOKEN);
        $etag = $initialResponse->getHeader('ETag');
        $response = $this->runGetRequest(sprintf('/%s', self::NODE_UUID), self::TOKEN, ['If-None-Match' => $etag]);
        $this->assertNotModifiedResponse($response);
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            'tests/Recordings/concepts/caching/not-modified-response-header.txt',
            $response
        );
    }
}
