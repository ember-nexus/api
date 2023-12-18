<?php

namespace App\Tests\FeatureTests;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

abstract class BaseRequestTestCase extends TestCase
{
    /**
     * @var string[] IGNORED_HEAD_HEADERS
     */
    private const array IGNORED_HEAD_HEADERS = ['X-Debug-Token', 'X-Debug-Token-Link', 'Date'];

    public function runGetRequest(string $uri, ?string $token): ResponseInterface
    {
        $headRequest = $this->runRequest('HEAD', $uri, $token);
        $getRequest = $this->runRequest('GET', $uri, $token);

        $headHeaders = $headRequest->getHeaders();
        $getHeaders = $getRequest->getHeaders();
        foreach ($headHeaders as $key => $value) {
            $this->assertArrayHasKey($key, $getHeaders);
            if (in_array($key, self::IGNORED_HEAD_HEADERS)) {
                continue;
            }
            $this->assertSame($value, $getHeaders[$key]);
        }

        return $getRequest;
    }

    public function runPostRequest(string $uri, ?string $token, array $data): ResponseInterface
    {
        return $this->runRequest('POST', $uri, $token, $data);
    }

    public function runPutRequest(string $uri, ?string $token, array $data): ResponseInterface
    {
        return $this->runRequest('PUT', $uri, $token, $data);
    }

    public function runPatchRequest(string $uri, ?string $token, array $data): ResponseInterface
    {
        return $this->runRequest('PATCH', $uri, $token, $data);
    }

    public function runDeleteRequest(string $uri, ?string $token): ResponseInterface
    {
        return $this->runRequest('DELETE', $uri, $token);
    }

    public function runOptionsRequest(string $uri, ?string $token): ResponseInterface
    {
        return $this->runRequest('OPTIONS', $uri, $token);
    }

    public function runHeadRequest(string $uri, ?string $token): ResponseInterface
    {
        return $this->runRequest('HEAD', $uri, $token);
    }

    public function runCopyRequest(string $uri, ?string $token): ResponseInterface
    {
        return $this->runRequest('COPY', $uri, $token);
    }

    public function runLockRequest(string $uri, ?string $token): ResponseInterface
    {
        return $this->runRequest('LOCK', $uri, $token);
    }

    public function runMkcolRequest(string $uri, ?string $token): ResponseInterface
    {
        return $this->runRequest('MKCOL', $uri, $token);
    }

    public function runMoveRequest(string $uri, ?string $token): ResponseInterface
    {
        return $this->runRequest('MOVE', $uri, $token);
    }

    public function runPropfindRequest(string $uri, ?string $token): ResponseInterface
    {
        return $this->runRequest('PROPFIND', $uri, $token);
    }

    public function runProppatchRequest(string $uri, ?string $token): ResponseInterface
    {
        return $this->runRequest('PROPPATCH', $uri, $token);
    }

    public function runUnlockRequest(string $uri, ?string $token): ResponseInterface
    {
        return $this->runRequest('UNLOCK', $uri, $token);
    }

    public function runRequest(string $method, string $uri, string $token = null, array $data = null): ResponseInterface
    {
        $client = new Client([
            'base_uri' => $_ENV['API_DOMAIN'],
            'http_errors' => false,
        ]);

        $options = [
            'headers' => [],
        ];
        if (null !== $token) {
            $options['headers']['Authorization'] = sprintf(
                'Bearer %s',
                $token
            );
        }

        if (null !== $data) {
            $options['headers']['Content-Type'] = 'application/json; charset=utf-8';
            $options['body'] = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return $client->request(
            $method,
            $uri,
            $options
        );
    }

    public function getBody(ResponseInterface $response): array
    {
        return \Safe\json_decode((string) $response->getBody(), true);
    }

    public function assertArrayHasNoNullValues(array $array): void
    {
        foreach ($array as $value) {
            $this->assertNotNull($value);
        }
    }

    public function assertIsCollectionResponse(ResponseInterface $response, int $countNodes = null, int $countRelations = null): void
    {
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertSame('_PartialCollection', $body['type']);
        $this->assertArrayHasKey('id', $body);
        $this->assertIsNumeric($body['totalNodes']);
        $this->assertIsArray($body['links']);
        $this->assertArrayHasKey('first', $body['links']);
        $this->assertArrayHasKey('previous', $body['links']);
        $this->assertArrayHasKey('next', $body['links']);
        $this->assertArrayHasKey('last', $body['links']);
        $this->assertIsArray($body['nodes']);
        $this->assertIsArray($body['relations']);
        if ($countNodes) {
            $this->assertCount($countNodes, $body['nodes']);
        }
        if ($countRelations) {
            $this->assertCount($countRelations, $body['relations']);
        }
    }

    /**
     * @param string[] $elementIds
     */
    public function assertIsElementCollectionResponse(ResponseInterface $response, int $countElements = null, array $elementIds = []): void
    {
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertSame('_PartialElementCollection', $body['type']);
        $this->assertArrayHasKey('id', $body);
        $this->assertIsNumeric($body['totalElements']);
        $this->assertIsArray($body['links']);
        $this->assertArrayHasKey('first', $body['links']);
        $this->assertArrayHasKey('previous', $body['links']);
        $this->assertArrayHasKey('next', $body['links']);
        $this->assertArrayHasKey('last', $body['links']);
        $this->assertIsArray($body['elements']);
        if ($countElements) {
            $this->assertCount($countElements, $body['elements']);
        }
        foreach ($elementIds as $elementId) {
            foreach ($body['elements'] as $element) {
                if ($element['id'] === $elementId) {
                    continue 2;
                }
            }
            $this->fail(sprintf(
                "Element with UUID '%s' not found.",
                $elementId
            ));
        }
    }

    public function assertIsNodeResponse(ResponseInterface $response, string $type): void
    {
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertSame($type, $body['type']);
        $this->assertArrayHasKey('id', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertIsArray($body['data']);
    }

    public function assertIsRelationResponse(ResponseInterface $response, string $type): void
    {
        $this->assertSame(200, $response->getStatusCode());

        $this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertSame($type, $body['type']);
        $this->assertArrayHasKey('id', $body);
        $this->assertArrayHasKey('data', $body);
        $this->assertArrayHasKey('start', $body);
        $this->assertArrayHasKey('end', $body);
        $this->assertIsArray($body['data']);
    }

    public function assertIsProblemResponse(ResponseInterface $response, int $status): void
    {
        $this->assertSame($status, $response->getStatusCode());

        $this->assertSame('application/problem+json; charset=utf-8', $response->getHeader('content-type')[0]);

        $body = \Safe\json_decode((string) $response->getBody(), true);

        $this->assertArrayHasKey('type', $body);
        $this->assertStringStartsWith('http', $body['type']);
        $this->assertArrayHasKey('title', $body);
        if (array_key_exists('detail', $body)) {
            $this->assertNotEmpty($body['detail']);
        }
        $this->assertArrayHasKey('status', $body);
        $this->assertSame($status, $body['status']);

        $typeDetailResponse = $this->runGetRequest($body['type'], null);
        $this->assertSame(
            200,
            $typeDetailResponse->getStatusCode(),
            sprintf(
                'Expected error type detail page with URL %s to be available, got HTTP status code %d.',
                $body['type'],
                $typeDetailResponse->getStatusCode()
            )
        );
    }

    public function assertIsCreatedResponse(ResponseInterface $response): void
    {
        $this->assertSame(201, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertIsString($response->getHeader('Location')[0]);
    }

    public function assertNoContentResponse(ResponseInterface $response): void
    {
        $this->assertSame(204, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertFalse($response->hasHeader('Location'));
    }

    public function assertIsDeletedResponse(ResponseInterface $response): void
    {
        $this->assertSame(204, $response->getStatusCode());
        $this->assertEmpty((string) $response->getBody());
        $this->assertFalse($response->hasHeader('Location'));
    }

    public function assertIsTextResponse(ResponseInterface $response, int $status): void
    {
        $this->assertSame($status, $response->getStatusCode());

        $this->assertSame('text/plain; charset=utf-8', $response->getHeader('content-type')[0]);
    }

    public function assertHasSingleOwner(string $token, string $childUuid, string $parentUuid): void
    {
        $parentsResponse = $this->runGetRequest(
            sprintf('/%s/parents', $childUuid),
            $token
        );
        $this->assertIsCollectionResponse($parentsResponse);
        $parentsResponseData = json_decode((string) $parentsResponse->getBody(), true);
        $this->assertSame(1, $parentsResponseData['totalNodes']);
        $this->assertSame($parentUuid, $parentsResponseData['nodes'][0]['id']);
        $this->assertSame('OWNS', $parentsResponseData['relations'][0]['type']);
    }

    public function assertIsCreatedBy(string $token, string $nodeUuid, string $userUuid): void
    {
        $relatedResponse = $this->runGetRequest(
            sprintf('/%s/related', $nodeUuid),
            $token
        );
        $this->assertIsCollectionResponse($relatedResponse);
        $relatedResponseData = json_decode((string) $relatedResponse->getBody(), true);
        foreach ($relatedResponseData['relations'] as $relation) {
            if ('CREATED' === $relation['type']) {
                $this->assertSame($userUuid, $relation['start']);

                return;
            }
        }
        $this->fail(sprintf(
            'Unable to find CREATED relation for node with UUID %s.',
            $nodeUuid
        ));
    }

    public function assertIsTokenWithState(ResponseInterface $response, string $state): void
    {
        $this->assertIsNodeResponse($response, 'Token');
        $tokenBody = $this->getBody($response);
        $this->assertSame($state, $tokenBody['data']['state']);
    }

    public function getUuidFromLocation(ResponseInterface $response): string
    {
        $location = $response->getHeader('Location')[0];

        return array_reverse(explode('/', $location))[0];
    }
}
