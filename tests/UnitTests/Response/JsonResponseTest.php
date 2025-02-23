<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Response;

use App\Response\JsonResponse;
use App\Type\Etag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(JsonResponse::class)]
class JsonResponseTest extends TestCase
{
    public function testEmptyJsonResponse(): void
    {
        $response = new JsonResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{}', $response->getContent());
        $this->assertSame('application/json; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('UTF-8', $response->getCharset());
    }

    public function testBasicJsonResponse(): void
    {
        $response = new JsonResponse(['some' => 'data']);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"some":"data"}', $response->getContent());
        $this->assertSame('application/json; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('UTF-8', $response->getCharset());
    }

    public function testSetEtagFromEtagInstance(): void
    {
        $response = new JsonResponse();
        $this->assertNull($response->getEtag());
        $etag = new Etag('some etag');
        $response->setEtagFromEtagInstance($etag);
        $this->assertSame('"some etag"', $response->getEtag());
    }
}
