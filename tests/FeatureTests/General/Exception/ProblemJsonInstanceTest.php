<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\General\Exception;

use App\Tests\FeatureTests\BaseRequestTestCase;
use Ramsey\Uuid\Uuid;

/**
 * Every problem json response identifies its request as `instance` (`urn:uuid:<id>`), which is also part of the logs.
 */
class ProblemJsonInstanceTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const string INSTANCE_PATTERN = '/^urn:uuid:[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    public function testNotFoundResponseContainsRequestIdAsInstance(): void
    {
        $response = $this->runGetRequest(sprintf('/%s', Uuid::uuid4()->toString()), self::TOKEN);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringStartsWith('application/problem+json', $response->getHeaderLine('Content-Type'));
        $body = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(['type', 'title', 'status', 'instance', 'detail'], array_slice(array_keys($body), 0, 5));
        $this->assertMatchesRegularExpression(self::INSTANCE_PATTERN, $body['instance']);
        // the id is only part of the body and of the logs
        $this->assertFalse($response->hasHeader('X-Request-Id'));
    }

    public function testEveryRequestGetsItsOwnInstance(): void
    {
        $instances = [];
        for ($i = 0; $i < 2; ++$i) {
            $response = $this->runGetRequest(sprintf('/%s', Uuid::uuid4()->toString()), self::TOKEN);
            $instances[] = json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR)['instance'];
        }

        $this->assertNotSame($instances[0], $instances[1]);
    }

    public function testResponsesWhichAreNoProblemsHaveNoInstance(): void
    {
        $response = $this->runGetRequest('/', self::TOKEN);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayNotHasKey('instance', json_decode((string) $response->getBody(), true, flags: JSON_THROW_ON_ERROR));
    }
}
