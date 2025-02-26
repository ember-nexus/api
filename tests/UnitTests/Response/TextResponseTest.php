<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Response;

use App\Response\TextResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(TextResponse::class)]
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
