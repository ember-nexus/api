<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Mirrors GetFileRangeTest, but targets a relation instead of a node. The relation and its deterministic file
 * are created once and shared by all tests of this class.
 */
class GetFileRangeOnRelationTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const int CONTENT_LENGTH = 5000;

    private static ?string $relationId = null;

    private function getRelationId(): string
    {
        if (null !== self::$relationId) {
            return self::$relationId;
        }

        $startNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'get-file-range-on-relation-start',
                ],
            ]
        );
        $startNodeId = $this->getUuidFromLocation($startNode);

        $endNode = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => 'get-file-range-on-relation-end',
                ],
            ]
        );
        $endNodeId = $this->getUuidFromLocation($endNode);

        $relation = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'start' => $startNodeId,
                'end' => $endNodeId,
                'data' => [
                    'name' => 'get-file-range-on-relation',
                ],
            ]
        );
        $relationId = $this->getUuidFromLocation($relation);

        $filePath = __DIR__.'/../../Asset/get-file-range-on-relation.bin';
        $this->generateDeterministicFile(24681012, self::CONTENT_LENGTH, $filePath);
        $file = \Safe\fopen($filePath, 'r');
        $uploadResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $relationId),
            $file,
            self::TOKEN,
            [
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertIsCreatedResponse($uploadResponse, false);
        unlink($filePath);

        self::$relationId = $relationId;

        return $relationId;
    }

    private function getFullBody(): string
    {
        $response = $this->runGetRequest(sprintf('/%s/file', $this->getRelationId()), self::TOKEN);
        $this->assertIsBinaryStreamResponse($response, 'text/plain');

        return (string) $response->getBody();
    }

    public function testExplicitRangeReturnsPartialContent(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', $this->getRelationId()),
            self::TOKEN,
            ['Range' => 'bytes=0-99']
        );

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame(['100'], $response->getHeader('Content-Length'));
        $this->assertSame([sprintf('bytes 0-99/%d', self::CONTENT_LENGTH)], $response->getHeader('Content-Range'));
        $this->assertSame(['bytes'], $response->getHeader('Accept-Ranges'));

        $body = (string) $response->getBody();
        $this->assertSame(100, strlen($body));
        $this->assertSame(substr($this->getFullBody(), 0, 100), $body);
    }

    public function testOpenEndedRangeReturnsBytesUntilEndOfFile(): void
    {
        $start = self::CONTENT_LENGTH - 50;

        $response = $this->runGetRequest(
            sprintf('/%s/file', $this->getRelationId()),
            self::TOKEN,
            ['Range' => sprintf('bytes=%d-', $start)]
        );

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame(
            [sprintf('bytes %d-%d/%d', $start, self::CONTENT_LENGTH - 1, self::CONTENT_LENGTH)],
            $response->getHeader('Content-Range')
        );

        $body = (string) $response->getBody();
        $this->assertSame(50, strlen($body));
        $this->assertSame(substr($this->getFullBody(), $start), $body);
    }

    public function testSuffixRangeReturnsLastBytesOfFile(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', $this->getRelationId()),
            self::TOKEN,
            ['Range' => 'bytes=-64']
        );

        $this->assertSame(206, $response->getStatusCode());
        $expectedStart = self::CONTENT_LENGTH - 64;
        $this->assertSame(
            [sprintf('bytes %d-%d/%d', $expectedStart, self::CONTENT_LENGTH - 1, self::CONTENT_LENGTH)],
            $response->getHeader('Content-Range')
        );

        $body = (string) $response->getBody();
        $this->assertSame(64, strlen($body));
        $this->assertSame(substr($this->getFullBody(), -64), $body);
    }

    public function testRangeStartBeyondEndOfFileReturnsRangeNotSatisfiable(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', $this->getRelationId()),
            self::TOKEN,
            ['Range' => 'bytes=999999999-']
        );

        $this->assertIsProblemResponse($response, 416);
        $body = \Safe\json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('totalLength', $body);
        $this->assertSame(self::CONTENT_LENGTH, $body['totalLength']);
    }

    public function testRequestWithoutRangeHeaderAdvertisesAcceptRanges(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', $this->getRelationId()), self::TOKEN);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['bytes'], $response->getHeader('Accept-Ranges'));
    }

    public function testIfRangeWithCurrentEtagServesPartialContent(): void
    {
        $etag = $this->runGetRequest(sprintf('/%s/file', $this->getRelationId()), self::TOKEN)->getHeader('ETag')[0];

        $response = $this->runGetRequest(
            sprintf('/%s/file', $this->getRelationId()),
            self::TOKEN,
            ['Range' => 'bytes=0-99', 'If-Range' => $etag]
        );

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame([sprintf('bytes 0-99/%d', self::CONTENT_LENGTH)], $response->getHeader('Content-Range'));
        $this->assertSame(100, strlen((string) $response->getBody()));
    }

    public function testIfRangeWithStaleEtagServesFullFile(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', $this->getRelationId()),
            self::TOKEN,
            ['Range' => 'bytes=0-99', 'If-Range' => '"stale-etag"']
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->getHeader('Content-Range'));
        $this->assertSame(self::CONTENT_LENGTH, strlen((string) $response->getBody()));
    }

    public function testIfRangeWithWeakEtagOrDateServesFullFile(): void
    {
        $etag = $this->runGetRequest(sprintf('/%s/file', $this->getRelationId()), self::TOKEN)->getHeader('ETag')[0];

        foreach (['W/'.$etag, 'Wed, 21 Oct 2015 07:28:00 GMT'] as $ifRange) {
            $response = $this->runGetRequest(
                sprintf('/%s/file', $this->getRelationId()),
                self::TOKEN,
                ['Range' => 'bytes=0-99', 'If-Range' => $ifRange]
            );

            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame(self::CONTENT_LENGTH, strlen((string) $response->getBody()));
        }
    }

    public function testIfRangeWithoutRangeIsIgnored(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', $this->getRelationId()),
            self::TOKEN,
            ['If-Range' => '"stale-etag"']
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(self::CONTENT_LENGTH, strlen((string) $response->getBody()));
    }
}
