<?php

namespace App\Tests\UnitTests\Response;

use App\Response\ElementResponse;
use PHPUnit\Framework\TestCase;

class ElementResponseTest extends TestCase
{
    public function testEmptyElementResponse(): void
    {
        $response = new ElementResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{}', $response->getContent());
        $this->assertSame('application/json; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('UTF-8', $response->getCharset());
    }

    public function testBasicElementResponse(): void
    {
        $response = new ElementResponse(['some' => 'data']);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"some":"data"}', $response->getContent());
        $this->assertSame('application/json; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('UTF-8', $response->getCharset());
    }
}
