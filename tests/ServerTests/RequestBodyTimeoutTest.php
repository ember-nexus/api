<?php

declare(strict_types=1);

namespace App\Tests\ServerTests;

use PHPUnit\Framework\Attributes\Group;

/**
 * Started with API_REQUEST_BODY_TIMEOUT=10s and API_UPLOAD_REQUEST_BODY_TIMEOUT=20s instead of 30s and 15min, see
 * docker/Caddyfile: only single request uploads (POST/PUT /<id>/file) and chunks (PATCH /upload/<id>) may take longer
 * than the default to send their request body.
 */
#[Group('server-timeouts')]
class RequestBodyTimeoutTest extends BaseServerTestCase
{
    public function testSlowBodyWithinDefaultTimeoutIsAccepted(): void
    {
        $body = $this->createElementBody();
        $request = $this->startCreateElementRequest($body);
        $request->sendBodySlowly($body, 3, 2.0);
        $response = $request->readResponse();

        $this->assertNotNull($response);
        $this->assertSame(201, $response->getStatusCode());
        $this->trackElementFromLocation($response);
    }

    public function testStalledBodyIsCutOffAfterDefaultTimeout(): void
    {
        $body = $this->createElementBody();
        $request = $this->startCreateElementRequest($body);
        $request->sendBody(substr($body, 0, 10));
        sleep(13);
        $request->sendBody(substr($body, 10));
        $response = $request->readResponse();

        $this->assertNotNull($response);
        $this->assertSame(408, $response->getStatusCode());
        $this->assertResponseMatchesDocumentation('request-body-timeout', $response, true);
    }

    public function testSlowUploadChunkBeyondDefaultButWithinUploadTimeoutIsAccepted(): void
    {
        [, $uploadId] = $this->createElementWithResumableUpload();

        $body = 'final chunk';
        $request = $this->startFinalChunkRequest($uploadId, strlen($body));
        // 15s, longer than the default timeout of 10s, but shorter than the one of upload endpoints of 20s
        $request->sendBodySlowly($body, 6, 3.0);
        $response = $request->readResponse();

        $this->assertNotNull($response);
        $this->assertSame(204, $response->getStatusCode());
    }

    public function testStalledUploadChunkIsCutOffAfterUploadTimeout(): void
    {
        [, $uploadId] = $this->createElementWithResumableUpload();

        $body = 'final chunk';
        $request = $this->startFinalChunkRequest($uploadId, strlen($body));
        $request->sendBody(substr($body, 0, 5));
        sleep(23);
        $request->sendBody(substr($body, 5));
        $response = $request->readResponse();

        $this->assertNotNull($response);
        $this->assertSame(408, $response->getStatusCode());
        $this->assertResponseMatchesDocumentation('upload-request-body-timeout', $response, true);

        // the upload was not modified and can be cleaned up
        $this->assertSame(204, $this->runDeleteRequest(sprintf('/upload/%s', $uploadId), $this->getToken())->getStatusCode());
    }
}
