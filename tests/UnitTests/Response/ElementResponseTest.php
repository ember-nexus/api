<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Response;

use App\Type\Etag;
use App\Type\Response\ElementResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ElementResponse::class)]
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

    public function testSetEtagFromEtagInstance(): void
    {
        $response = new ElementResponse();
        $this->assertNull($response->getEtag());
        $etag = new Etag('some etag');
        $response->setEtagFromEtagInstance($etag);
        $this->assertSame('"some etag"', $response->getEtag());
    }
}
