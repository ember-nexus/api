<?php

declare(strict_types=1);

namespace App\Tests\ExampleGenerationControllerWithDifferentConfiguration1\Upload;

use App\Tests\ExampleGenerationControllerWithDefaultConfiguration\BaseRequestTestCase;

/**
 * Runs with `file.uploadExpiresAfterFirstRequestInSeconds` set to 3, so an upload can be driven past its expiry
 * within a test without waiting on the (much longer) default. Must be run via
 * `composer test:example-generation-controller:with-different-configuration-1`, otherwise the default expiry
 * applies and the upload never goes `Gone` within the test.
 */
class PatchUploadGoneTest extends BaseRequestTestCase
{
    private const string PATH_TO_ROOT = __DIR__.'/../../../';
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testPatchUploadFailure410Gone(): void
    {
        $elementId = 'a1b2c3d4-e5f6-4a1b-8c2d-3e4f5a6b7c8d';
        $this->createDocumentedElement($elementId, 'patch-upload-410-gone', self::TOKEN);

        $createResponse = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            '',
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($createResponse, true);
        $uploadId = $this->getUuidFromLocation($createResponse);

        // the configured upload expiry is 3 seconds; wait it out so the upload is `Gone` before the next request
        sleep(4);

        $response = $this->runUploadRequest(
            'PATCH',
            sprintf('/upload/%s', $uploadId),
            '',
            self::TOKEN,
            [
                'Upload-Complete' => '?1',
                'Upload-Offset' => 0,
                'Content-Type' => 'application/partial-upload',
            ]
        );
        $this->assertIsProblemResponse($response, 410);

        $documentationHeadersPath = 'tests/Recordings/api-endpoints/upload/patch-upload/410-response-header.txt';
        $documentationBodyPath = 'tests/Recordings/api-endpoints/upload/patch-upload/410-response-body.json';
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
