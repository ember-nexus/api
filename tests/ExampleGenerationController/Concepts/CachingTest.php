<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationController\Concepts;

use App\Tests\ExampleGenerationController\BaseRequestTestCase;

class CachingTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:PIPeJGUt7c00ENn8a5uDlc';
    private const string NODE_UUID = '74a8fcd9-6cb0-4b0d-8d42-0b6c3c54d1ac';

    public function testInitialRequest(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', self::NODE_UUID), self::TOKEN);
        $this->assertIsNodeResponse($response, 'Comment');
        $this->assertHeadersInDocumentationAreIdenticalToHeadersFromRequest(
            self::PATH_TO_ROOT,
            'docs/concepts/caching/initial-request-response-header.txt',
            $response
        );
        $this->assertBodyInDocumentationIsIdenticalToBodyFromRequest(
            self::PATH_TO_ROOT,
            'docs/concepts/caching/initial-request-response-body.json',
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
            'docs/concepts/caching/not-modified-response-header.txt',
            $response
        );
    }
}
