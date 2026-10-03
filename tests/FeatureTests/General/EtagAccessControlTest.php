<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Conditional requests must not reveal whether an element exists or has a file: without access they are answered
 * exactly like a request for a non-existing element, no matter which precondition headers are sent.
 */
class EtagAccessControlTest extends BaseRequestTestCase
{
    private const string OWNER_TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const string OTHER_TOKEN = 'secret-token:RRq4WsomBeTH0AAa7Jmi4k';
    private const string UNKNOWN_ID = '3f0d6d0e-8d0e-4b8e-9f0e-5b3a8d0e6c11';

    /**
     * @return array<string, array{string}>
     */
    public static function preconditionHeaderProvider(): array
    {
        return [
            'If-None-Match wildcard' => ['If-None-Match: *'],
            'If-Match wildcard' => ['If-Match: *'],
            'If-Match etag' => ['If-Match: "someEtag"'],
            'If-None-Match etag' => ['If-None-Match: "someEtag"'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parseHeader(string $header): array
    {
        [$name, $value] = explode(': ', $header, 2);

        return [$name => $value];
    }

    #[DataProvider('preconditionHeaderProvider')]
    public function testReadEndpointsWithoutAccessAnswerLikeForMissingElements(string $header): void
    {
        $headers = $this->parseHeader($header);
        $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::OWNER_TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'etag-access-control-read'],
        ]));

        foreach (['', '/children', '/parents', '/related', '/file'] as $suffix) {
            $this->assertIsProblemResponse(
                $this->runGetRequest(sprintf('/%s%s', $elementId, $suffix), self::OTHER_TOKEN, $headers),
                404
            );
            // same answer as for an element which does not exist
            $this->assertIsProblemResponse(
                $this->runGetRequest(sprintf('/%s%s', self::UNKNOWN_ID, $suffix), self::OTHER_TOKEN, $headers),
                404
            );
        }

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::OWNER_TOKEN));
    }

    #[DataProvider('preconditionHeaderProvider')]
    public function testModifyingEndpointsWithoutAccessAnswerLikeForMissingElements(string $header): void
    {
        $headers = $this->parseHeader($header);
        $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::OWNER_TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'etag-access-control-modify'],
        ]));
        $this->assertIsCreatedResponse(
            $this->runUploadRequest('POST', sprintf('/%s/file', $elementId), 'content', self::OWNER_TOKEN),
            false
        );

        foreach ([$elementId, self::UNKNOWN_ID] as $id) {
            $this->assertIsProblemResponse($this->runPatchRequest(sprintf('/%s', $id), self::OTHER_TOKEN, ['name' => 'x'], $headers), 404);
            $this->assertIsProblemResponse($this->runPutRequest(sprintf('/%s', $id), self::OTHER_TOKEN, ['name' => 'x'], $headers), 404);
            $this->assertIsProblemResponse($this->runDeleteRequest(sprintf('/%s', $id), self::OTHER_TOKEN, $headers), 404);
            $this->assertIsProblemResponse($this->runDeleteRequest(sprintf('/%s/file', $id), self::OTHER_TOKEN, $headers), 404);
            foreach (['POST', 'PUT'] as $method) {
                $this->assertIsProblemResponse(
                    $this->runUploadRequest($method, sprintf('/%s/file', $id), 'other', self::OTHER_TOKEN, $headers),
                    404
                );
            }
        }

        // nothing was modified
        $this->assertSame('content', (string) $this->runGetRequest(sprintf('/%s/file', $elementId), self::OWNER_TOKEN)->getBody());
        $body = $this->getBody($this->runGetRequest(sprintf('/%s', $elementId), self::OWNER_TOKEN));
        $this->assertSame('etag-access-control-modify', $body['data']['name']);

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::OWNER_TOKEN));
    }
}
