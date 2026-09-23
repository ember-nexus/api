<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Mirrors GetFileRangeTest, but exercises the `Range` header on `GET /<id>/file` against a relation instead of
 * a node. Since the reference dataset does not seed any relation with a file, a relation and a deterministic
 * file are created fresh for this test.
 */
#[Group('test')]
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
        $this->assertArrayHasKey('total-length', $body);
        $this->assertSame(self::CONTENT_LENGTH, $body['total-length']);
    }

    public function testRequestWithoutRangeHeaderAdvertisesAcceptRanges(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', $this->getRelationId()), self::TOKEN);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['bytes'], $response->getHeader('Accept-Ranges'));
    }
}
