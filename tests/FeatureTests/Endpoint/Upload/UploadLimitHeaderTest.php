<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\Upload;

use App\Tests\FeatureTests\BaseRequestTestCase;
use DateTimeImmutable;
use Laudis\Neo4j\ClientBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\ResponseInterface;

/**
 * `max-age` of the `Upload-Limit` header is the remaining lifetime of the upload in seconds (never negative) and
 * consistent with the `Expires` header.
 */
class UploadLimitHeaderTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    /**
     * @return array<string, array{0: bool}>
     */
    public static function targetProvider(): array
    {
        return ['node' => [false], 'relation' => [true]];
    }

    private function setExpiresInSeconds(string $uploadId, int $seconds): void
    {
        $client = ClientBuilder::create()->withDriver('bolt', $_ENV['CYPHER_AUTH'])->build();
        $result = $client->run(
            'MATCH (u:Upload {id: $id}) SET u.expires = datetime() + duration({seconds: $seconds}) RETURN count(u) AS count',
            ['id' => $uploadId, 'seconds' => $seconds]
        );
        $this->assertSame(1, $result->first()->get('count'));
    }

    private function getMaxAge(ResponseInterface $response): int
    {
        $this->assertSame(1, preg_match('/max-age=(-?\d+),/', $response->getHeader('Upload-Limit')[0], $matches));

        return (int) $matches[1];
    }

    private function getSecondsUntilExpiresHeader(ResponseInterface $response): int
    {
        return (new DateTimeImmutable($response->getHeader('Expires')[0]))->getTimestamp() - time();
    }

    #[DataProvider('targetProvider')]
    public function testMaxAgeIsRemainingLifetimeAndConsistentWithExpires(bool $onRelation): void
    {
        $elementId = $onRelation
            ? $this->createEphemeralRelation(self::TOKEN, 'upload-limit-header')
            : $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, ['type' => 'Data', 'data' => ['name' => 'upload-limit-header']]));

        $createResponse = $this->runUploadRequest('POST', sprintf('/%s/file', $elementId), '', self::TOKEN, [
            'Upload-Complete' => '?0',
            'Content-Type' => 'application/octet-stream',
        ]);
        $this->assertNoContentResponse($createResponse, true);
        $uploadId = $this->getUuidFromLocation($createResponse);

        // fresh upload: the remaining lifetime is (almost) the configured window
        $fullWindow = $this->getMaxAge($createResponse);
        $this->assertGreaterThan(0, $fullWindow);
        $this->assertEqualsWithDelta($fullWindow, $this->getSecondsUntilExpiresHeader($createResponse), 5);

        $this->setExpiresInSeconds($uploadId, 300);
        foreach ([
            $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN),
            $this->runUploadRequest('PATCH', sprintf('/upload/%s', $uploadId), '', self::TOKEN, [
                'Upload-Complete' => '?0',
                'Upload-Offset' => 0,
                'Content-Type' => 'application/partial-upload',
            ]),
        ] as $response) {
            $this->assertSame(204, $response->getStatusCode());
            $maxAge = $this->getMaxAge($response);
            $this->assertLessThanOrEqual(300, $maxAge);
            $this->assertGreaterThanOrEqual(290, $maxAge);
            $this->assertLessThan($fullWindow, $maxAge);
            $this->assertEqualsWithDelta($maxAge, $this->getSecondsUntilExpiresHeader($response), 5);
        }

        // expired, but not yet removed by the cron job: never negative
        $this->setExpiresInSeconds($uploadId, -3600);
        $expiredResponse = $this->runHeadRequest(sprintf('/upload/%s', $uploadId), self::TOKEN);
        $this->assertSame(204, $expiredResponse->getStatusCode());
        $this->assertSame(0, $this->getMaxAge($expiredResponse));

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/upload/%s', $uploadId), self::TOKEN));
        if ($onRelation) {
            $this->deleteEphemeralRelation(self::TOKEN, $elementId);
        } else {
            $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
        }
    }
}
