<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\File;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * Verifies that `POST /<uuid>/file` evaluates ETag preconditions. Unlike PUT/DELETE, POST only targets elements
 * without a file, whose file etag a client can not obtain (`GET /<uuid>/file` answers 404), so `If-Match` can be
 * rejected but never satisfied.
 */
class PostFileEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:M3WHIDj4q62EY0XiZFMLnv';

    private function createElement(string $name): string
    {
        $response = $this->runPostRequest(
            '/',
            self::TOKEN,
            [
                'type' => 'Data',
                'data' => [
                    'name' => $name,
                ],
            ]
        );

        return $this->getUuidFromLocation($response);
    }

    /**
     * @param array<string, string> $additionalHeaders
     */
    private function postFile(string $elementId, array $additionalHeaders = []): mixed
    {
        $filePath = __DIR__.'/../../Asset/post-file-etag.bin';
        $this->generateDeterministicFile(19283746, 64, $filePath);
        $file = \Safe\fopen($filePath, 'r');
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            $file,
            self::TOKEN,
            array_merge(['Content-Type' => 'application/octet-stream'], $additionalHeaders)
        );
        unlink($filePath);

        return $response;
    }

    public function testPostFileIsRejectedWhenIfMatchDoesNotMatch(): void
    {
        $elementId = $this->createElement('post-file-etag-if-match-mismatch');

        $response = $this->postFile($elementId, ['If-Match' => '"definitelyNotTheEtag"']);
        $this->assertIsProblemResponse($response, 412);

        // the precondition failed, so no file may have been created
        $this->assertIsProblemResponse($this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN), 404);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testPostFileIsRejectedWhenIfMatchCarriesTheElementEtag(): void
    {
        $elementId = $this->createElement('post-file-etag-element-etag-does-not-apply');

        $elementEtag = $this->runGetRequest(sprintf('/%s', $elementId), self::TOKEN)->getHeader('Etag')[0];

        // the element etag is not the file etag, even for an element which has no file: calculateFileEtag()
        // derives its own value from the element etag rather than reusing it
        $this->assertIsProblemResponse($this->postFile($elementId, ['If-Match' => $elementEtag]), 412);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    public function testPostFileWithoutPreconditionHeadersStillWorks(): void
    {
        $elementId = $this->createElement('post-file-etag-no-precondition');

        $this->assertIsCreatedResponse($this->postFile($elementId), false);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }

    /**
     * A non-matching If-None-Match is a precondition which passes, so the request proceeds normally.
     */
    public function testPostFileProceedsWhenIfNoneMatchDoesNotMatch(): void
    {
        $elementId = $this->createElement('post-file-etag-if-none-match');

        $response = $this->postFile($elementId, ['If-None-Match' => '"definitelyNotTheEtag"']);
        $this->assertIsCreatedResponse($response, false);

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }
}
