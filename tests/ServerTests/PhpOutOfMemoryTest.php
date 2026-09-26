<?php

declare(strict_types=1);

namespace App\Tests\ServerTests;

use PHPUnit\Framework\Attributes\Group;

/**
 * Started with the default limits of the production image (`memory_limit = 256M`, `display_errors = Off`). The fatal
 * error is turned into a regular problem response by the application (Symfony's error handler), not by the web server.
 */
#[Group('server-php-failure')]
class PhpOutOfMemoryTest extends BaseServerTestCase
{
    public function testExhaustedMemoryIsAnsweredWithProblemJsonOfTheApplication(): void
    {
        // the body is far below the limit of the web server (101 MiB), but decoding needs more than 256 MiB
        $body = fopen('php://temp', 'r+');
        fwrite($body, '{"type":"Data","data":{"name":"too big","values":[0');
        $part = str_repeat(',0', 1_000_000);
        for ($i = 0; $i < 20; ++$i) {
            fwrite($body, $part);
        }
        fwrite($body, ']}}');
        rewind($body);

        $response = $this->runUploadRequest(
            'POST',
            '/',
            $body,
            $this->getToken(),
            ['Content-Type' => 'application/json; charset=utf-8']
        );

        $this->assertSame(500, $response->getStatusCode());
        $this->assertResponseMatchesDocumentation('php-out-of-memory', $response, true);
        $instanceOfErrorResponse = $this->getInstanceOfProblemResponse($response);
        $responseBody = (string) $response->getBody();
        $this->assertStringNotContainsStringIgnoringCase('memory', $responseBody);
        $this->assertStringNotContainsStringIgnoringCase('fatal', $responseBody);

        // the server keeps working afterwards
        $followUpResponse = $this->runGetRequest('/', $this->getToken());
        $this->assertSame(200, $followUpResponse->getStatusCode());
        $this->assertNotSame($instanceOfErrorResponse, $this->getInstanceOfProblemResponse($this->runNotFoundRequest()));
    }
}
