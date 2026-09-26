<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Endpoint\System;

use App\Tests\FeatureTests\BaseRequestTestCase;

class MethodNotAllowedTest extends BaseRequestTestCase
{
    private const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';

    public function testUnsupportedMethodOnExistingRouteReturnsProblemJsonWithAllowHeader(): void
    {
        // `/register` only exists for POST
        $response = $this->runDeleteRequest('/register', null);

        $this->assertIsProblemResponse($response, 405);
        $body = \Safe\json_decode((string) $response->getBody(), true);
        $this->assertStringEndsWith('/error/405/method-not-allowed', $body['type']);
        $this->assertSame('Method not allowed', $body['title']);
        $this->assertSame(405, $body['status']);

        // exactly one Allow header, listing only the methods of this route (not the global list of all methods)
        $this->assertSame(['POST'], $response->getHeader('Allow'));
    }

    public function testUnknownRouteStillReturnsNotFound(): void
    {
        $response = $this->runDeleteRequest('/this/route/does/not/exist', null);

        $this->assertIsProblemResponse($response, 404);
        $this->assertNotSame(['POST'], $response->getHeader('Allow'));
    }

    public function testResponseOfMatchedRouteListsOnlyMethodsOfThisRoute(): void
    {
        $response = $this->runGetRequest('/.well-known/security.txt', null);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['GET, HEAD, OPTIONS'], $response->getHeader('Allow'));
    }

    public function testResponseOfElementRouteListsAllMethodsOfTheElementEndpoints(): void
    {
        // the element does not exist, the error response of a matched route carries the header as well
        $response = $this->runGetRequest('/b4117ae0-1241-479f-b363-45f290ec7fc7', self::TOKEN);

        $this->assertIsProblemResponse($response, 404);
        // all controllers for `/{id}`, including POST (create child) and the WebDAV methods
        $this->assertSame(
            ['COPY, DELETE, GET, HEAD, LOCK, MKCOL, MOVE, OPTIONS, PATCH, POST, PROPFIND, PROPPATCH, PUT, UNLOCK'],
            $response->getHeader('Allow')
        );
    }
}
