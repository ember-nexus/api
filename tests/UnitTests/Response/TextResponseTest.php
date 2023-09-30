<?php

namespace App\tests\UnitTests\Response;

use App\Response\TextResponse;
use PHPUnit\Framework\TestCase;

class TextResponseTest extends TestCase
{
    public function testEmptyTextResponse(): void
    {
        $response = new TextResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
        $this->assertSame('text/plain; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('UTF-8', $response->getCharset());
    }

    public function testBasicTextResponse(): void
    {
        $response = new TextResponse('some content');
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('some content', $response->getContent());
        $this->assertSame('text/plain; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('UTF-8', $response->getCharset());
    }
}
