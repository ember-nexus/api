<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;

/**
 * A resumable upload started by `POST /<id>/file` (`Upload-Complete: ?0`) can be overtaken by a concurrent `PUT`,
 * which is allowed to race and creates the file directly. Once that happens, the still-open upload can no longer
 * complete safely: appending another chunk to it, or finishing it, has to be rejected instead of silently
 * overwriting (or losing) the file the `PUT` just created.
 */
class ResumableUploadFinalizeRaceTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    private function createElement(): string
    {
        return $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'resumable-upload-finalize-race'],
        ]));
    }

    private function createOpenUpload(string $elementId): string
    {
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            '',
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($response, true);

        return $this->getUuidFromLocation($response);
    }

    public function testChunkIsRejectedAfterAConcurrentPutCreatedTheFile(): void
    {
        $elementId = $this->createElement();
        $uploadId = $this->createOpenUpload($elementId);

        // a concurrent PUT is allowed to race the still-open upload and creates the file directly
        $putResponse = $this->runUploadRequest(
            'PUT',
            sprintf('/%s/file', $elementId),
            'winner of the race',
            self::TOKEN,
            ['Content-Type' => 'text/plain']
        );
        $this->assertContains($putResponse->getStatusCode(), [200, 201, 204]);

        // the open upload can no longer complete safely: appending to it now has to be rejected
        $patchResponse = $this->runUploadRequest(
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
        $this->assertIsProblemResponse($patchResponse, 409);
        $body = $this->getBody($patchResponse);
        $this->assertStringContainsString('already has an associated file', $body['detail']);

        // the file created by PUT is untouched by the rejected upload
        $downloadResponse = $this->runGetRequest(sprintf('/%s/file', $elementId), self::TOKEN);
        $this->assertSame('winner of the race', (string) $downloadResponse->getBody());

        $this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN);
    }
}
