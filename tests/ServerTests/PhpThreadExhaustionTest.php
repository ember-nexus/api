<?php

declare(strict_types=1);

namespace App\Tests\ServerTests;

use PHPUnit\Framework\Attributes\Group;

/**
 * Started with a single PHP thread (FRANKENPHP_NUM_THREADS=1, FRANKENPHP_MAX_THREADS=1), FRANKENPHP_MAX_WAIT_TIME=2s
 * and API_UPLOAD_REQUEST_BODY_TIMEOUT=20s, see docker/Caddyfile.
 */
#[Group('server-threads')]
class PhpThreadExhaustionTest extends BaseServerTestCase
{
    public function testRequestWithoutFreePhpThreadIsAnsweredWithServiceUnavailable(): void
    {
        [, $uploadId] = $this->createElementWithResumableUpload();

        // the slow upload request occupies the only PHP thread while it waits for the rest of its body
        $body = 'final chunk';
        $slowRequest = $this->startFinalChunkRequest($uploadId, strlen($body));
        $slowRequest->sendBody(substr($body, 0, 3));
        sleep(1);

        $start = microtime(true);
        $response = $this->runGetRequest('/', $this->getToken());
        $duration = microtime(true) - $start;

        $this->assertSame(503, $response->getStatusCode());
        $this->assertGreaterThan(1.5, $duration, 'The request should have waited for a free thread.');
        $this->assertLessThan(10, $duration);
        $this->assertResponseMatchesDocumentation('no-free-php-thread', $response);

        // the server recovers as soon as the thread is free again
        $slowRequest->sendBody(substr($body, 3));
        $slowResponse = $slowRequest->readResponse();
        $this->assertNotNull($slowResponse);
        $this->assertSame(204, $slowResponse->getStatusCode());
        $this->assertSame(200, $this->runGetRequest('/', $this->getToken())->getStatusCode());
    }
}
