<?php

declare(strict_types=1);

namespace App\Tests\EndToEndTests\General;

use App\Tests\EndToEndTests\BaseRequestTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * `If-None-Match: *` matches every current representation, `If-Match: *` is satisfied by every current
 * representation (RFC 9110). Covers all read endpoints which support ETags: nodes, relations and collections.
 *
 * Read-only requests use runRequest() instead of runGetRequest(): the latter compares the headers of a HEAD and a GET
 * request, which differ if another test changes the same collection in between.
 */
class WildcardEtagTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:RRq4WsomBeTH0AAa7Jmi4k';
    // separate user for tests which create elements: the index ETag of the user above is asserted by other tests
    private const string WRITE_TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    private const string ID_DATA = '02IiNr0EPf9nVLBIuHYAwqBZoaz85PTYYstCi';
    private const string ID_PARENT = '02IiNr0EPf9nVLAPZlLLN9h0MVh8ly8VrFCb8';
    private const string ID_CHILD = '02IiNr0EPf9nVLCA8YZ3annMXymmQYJJ0uq5q';
    private const string ID_RELATION = '02IiNr0EPf9nVLBedZqZn38dBHiUSvac95XDo';

    /**
     * @return array<string, array{string}>
     */
    public static function readEndpointProvider(): array
    {
        return [
            'node' => [sprintf('/%s', self::ID_DATA)],
            'relation' => [sprintf('/%s', self::ID_RELATION)],
            'index collection' => ['/'],
            'children collection' => [sprintf('/%s/children', self::ID_PARENT)],
            'parents collection' => [sprintf('/%s/parents', self::ID_CHILD)],
            'related collection' => [sprintf('/%s/related', self::ID_PARENT)],
        ];
    }

    #[DataProvider('readEndpointProvider')]
    public function testIfNoneMatchWildcardReturns304(string $uri): void
    {
        $this->assertNotModifiedResponse($this->runRequest('GET', $uri, self::TOKEN, headers: ['If-None-Match' => '*']));
    }

    #[DataProvider('readEndpointProvider')]
    public function testIfNoneMatchWildcardInListReturns304(string $uri): void
    {
        $this->assertNotModifiedResponse($this->runRequest('GET', $uri, self::TOKEN, headers: ['If-None-Match' => '"etagDoesNotExist", *']));
    }

    #[DataProvider('readEndpointProvider')]
    public function testIfMatchWildcardIsSatisfied(string $uri): void
    {
        $response = $this->runRequest('GET', $uri, self::TOKEN, headers: ['If-Match' => '*']);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testIfNoneMatchWildcardReturns412OnModifyingRequests(): void
    {
        $elementId = $this->getUuidFromLocation($this->runPostRequest('/', self::WRITE_TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'wildcard-etag-modify-node'],
        ]));

        $this->assertIsProblemResponse(
            $this->runPatchRequest(sprintf('/%s', $elementId), self::WRITE_TOKEN, ['name' => 'changed'], ['If-None-Match' => '*']),
            412
        );
        $this->assertIsProblemResponse(
            $this->runPutRequest(sprintf('/%s', $elementId), self::WRITE_TOKEN, ['name' => 'changed'], ['If-None-Match' => '*']),
            412
        );
        $this->assertIsProblemResponse(
            $this->runDeleteRequest(sprintf('/%s', $elementId), self::WRITE_TOKEN, ['If-None-Match' => '*']),
            412
        );

        // nothing was changed by the rejected requests
        $body = $this->getBody($this->runGetRequest(sprintf('/%s', $elementId), self::WRITE_TOKEN));
        $this->assertSame('wildcard-etag-modify-node', $body['data']['name']);

        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::WRITE_TOKEN));
    }

    public function testIfMatchWildcardAllowsModifyingRequestsOnNodeAndRelation(): void
    {
        $nodeId = $this->getUuidFromLocation($this->runPostRequest('/', self::WRITE_TOKEN, [
            'type' => 'Data',
            'data' => ['name' => 'wildcard-etag-if-match-node'],
        ]));
        $this->assertNoContentResponse(
            $this->runPatchRequest(sprintf('/%s', $nodeId), self::WRITE_TOKEN, ['name' => 'changed'], ['If-Match' => '*'])
        );
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $nodeId), self::WRITE_TOKEN, ['If-Match' => '*']));

        $relationId = $this->createEphemeralRelation(self::WRITE_TOKEN, 'wildcard-etag-if-match-relation');
        $this->assertNoContentResponse(
            $this->runPatchRequest(sprintf('/%s', $relationId), self::WRITE_TOKEN, ['name' => 'changed'], ['If-Match' => '*'])
        );
        $this->deleteEphemeralRelation(self::WRITE_TOKEN, $relationId);
    }
}
