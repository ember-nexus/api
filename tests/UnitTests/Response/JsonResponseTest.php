<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Response;

use App\Response\JsonResponse;
use PHPUnit\Framework\TestCase;

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
}
