<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Exercises the `Range` header on `GET /<id>/file` against the read-only "Rose" image from the
 * "general.botanicExample" reference dataset scenario (138937 bytes).
 */
class GetFileRangeTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const string ROSE_ID = '0fdd52ba-55da-430c-b015-3277a231e895';
    private const int ROSE_CONTENT_LENGTH = 138937;

    private function getFullBody(): string
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::ROSE_ID), self::TOKEN);
        $this->assertIsBinaryStreamResponse($response, 'image/jpeg');

        return (string) $response->getBody();
    }

    public function testExplicitRangeReturnsPartialContent(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'bytes=0-99']
        );

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame(['100'], $response->getHeader('Content-Length'));
        $this->assertSame([sprintf('bytes 0-99/%d', self::ROSE_CONTENT_LENGTH)], $response->getHeader('Content-Range'));
        $this->assertSame(['bytes'], $response->getHeader('Accept-Ranges'));

        $body = (string) $response->getBody();
        $this->assertSame(100, strlen($body));
        $this->assertSame(substr($this->getFullBody(), 0, 100), $body);
    }

    public function testOpenEndedRangeReturnsBytesUntilEndOfFile(): void
    {
        $start = self::ROSE_CONTENT_LENGTH - 50;

        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => sprintf('bytes=%d-', $start)]
        );

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame(
            [sprintf('bytes %d-%d/%d', $start, self::ROSE_CONTENT_LENGTH - 1, self::ROSE_CONTENT_LENGTH)],
            $response->getHeader('Content-Range')
        );

        $body = (string) $response->getBody();
        $this->assertSame(50, strlen($body));
        $this->assertSame(substr($this->getFullBody(), $start), $body);
    }

    public function testSuffixRangeReturnsLastBytesOfFile(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'bytes=-64']
        );

        $this->assertSame(206, $response->getStatusCode());
        $expectedStart = self::ROSE_CONTENT_LENGTH - 64;
        $this->assertSame(
            [sprintf('bytes %d-%d/%d', $expectedStart, self::ROSE_CONTENT_LENGTH - 1, self::ROSE_CONTENT_LENGTH)],
            $response->getHeader('Content-Range')
        );

        $body = (string) $response->getBody();
        $this->assertSame(64, strlen($body));
        $this->assertSame(substr($this->getFullBody(), -64), $body);
    }

    public function testEndBeyondFileLengthIsClampedInsteadOfRejected(): void
    {
        $start = self::ROSE_CONTENT_LENGTH - 10;

        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => sprintf('bytes=%d-99999999', $start)]
        );

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame(
            [sprintf('bytes %d-%d/%d', $start, self::ROSE_CONTENT_LENGTH - 1, self::ROSE_CONTENT_LENGTH)],
            $response->getHeader('Content-Range')
        );
        $this->assertSame(10, strlen((string) $response->getBody()));
    }

    public function testRangeStartBeyondEndOfFileReturnsRangeNotSatisfiable(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'bytes=999999999-']
        );

        $this->assertIsProblemResponse($response, 416);
        $body = \Safe\json_decode((string) $response->getBody(), true);
        $this->assertArrayHasKey('totalLength', $body);
        $this->assertSame(self::ROSE_CONTENT_LENGTH, $body['totalLength']);
    }

    public function testMalformedRangeHeaderReturnsBadContent(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'not-a-valid-range']
        );

        $this->assertIsProblemResponse($response, 400);
    }

    public function testMultipleRangesReturnsBadContent(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'bytes=0-10,20-30']
        );

        $this->assertIsProblemResponse($response, 400);
    }

    public function testRequestWithoutRangeHeaderAdvertisesAcceptRanges(): void
    {
        $response = $this->runGetRequest(sprintf('/%s/file', self::ROSE_ID), self::TOKEN);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['bytes'], $response->getHeader('Accept-Ranges'));
    }

    public function testIfRangeWithCurrentEtagServesPartialContent(): void
    {
        $etag = $this->runGetRequest(sprintf('/%s/file', self::ROSE_ID), self::TOKEN)->getHeader('ETag')[0];

        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'bytes=0-99', 'If-Range' => $etag]
        );

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame([sprintf('bytes 0-99/%d', self::ROSE_CONTENT_LENGTH)], $response->getHeader('Content-Range'));
        $this->assertSame(100, strlen((string) $response->getBody()));
    }

    public function testIfRangeWithStaleEtagServesFullFile(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'bytes=0-99', 'If-Range' => '"stale-etag"']
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->getHeader('Content-Range'));
        $this->assertSame(self::ROSE_CONTENT_LENGTH, strlen((string) $response->getBody()));
    }

    public function testIfRangeWithWeakEtagOrDateServesFullFile(): void
    {
        $etag = $this->runGetRequest(sprintf('/%s/file', self::ROSE_ID), self::TOKEN)->getHeader('ETag')[0];

        foreach (['W/'.$etag, 'Wed, 21 Oct 2015 07:28:00 GMT'] as $ifRange) {
            $response = $this->runGetRequest(
                sprintf('/%s/file', self::ROSE_ID),
                self::TOKEN,
                ['Range' => 'bytes=0-99', 'If-Range' => $ifRange]
            );

            $this->assertSame(200, $response->getStatusCode());
            $this->assertSame(self::ROSE_CONTENT_LENGTH, strlen((string) $response->getBody()));
        }
    }

    public function testIfRangeWithoutRangeIsIgnored(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['If-Range' => '"stale-etag"']
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(self::ROSE_CONTENT_LENGTH, strlen((string) $response->getBody()));
    }

    public function testPartialContentResponseCarriesTheSameFileEtagAsTheFullResponse(): void
    {
        $fullResponse = $this->runGetRequest(sprintf('/%s/file', self::ROSE_ID), self::TOKEN);
        $fullEtag = $fullResponse->getHeader('ETag')[0];

        $rangeResponse = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'bytes=0-99']
        );

        $this->assertSame(206, $rangeResponse->getStatusCode());
        $this->assertCount(1, $rangeResponse->getHeader('ETag'));
        $this->assertSame($fullEtag, $rangeResponse->getHeader('ETag')[0]);
    }

    public function testRangeRequestWithMatchingIfNoneMatchReturnsNotModifiedInsteadOfPartialContent(): void
    {
        $etag = $this->runGetRequest(sprintf('/%s/file', self::ROSE_ID), self::TOKEN)->getHeader('ETag')[0];

        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'bytes=0-99', 'If-None-Match' => $etag]
        );

        $this->assertNotModifiedResponse($response);
        $this->assertSame([], $response->getHeader('Content-Range'));
    }

    public function testRangeRequestWithNonMatchingIfNoneMatchStillReturnsPartialContent(): void
    {
        $response = $this->runGetRequest(
            sprintf('/%s/file', self::ROSE_ID),
            self::TOKEN,
            ['Range' => 'bytes=0-99', 'If-None-Match' => '"staleEtagWhichNeverMatches"']
        );

        $this->assertSame(206, $response->getStatusCode());
        $this->assertSame(100, strlen((string) $response->getBody()));
    }
}
