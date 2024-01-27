<?php

namespace App\tests\UnitTests\Response;

use App\Response\CollectionResponse;
use PHPUnit\Framework\TestCase;

class CollectionResponseTest extends TestCase
{
    public function testEmptyCollectionResponse(): void
    {
        $response = new CollectionResponse();
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{}', $response->getContent());
        $this->assertSame('application/json; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('UTF-8', $response->getCharset());
    }

    public function testBasicCollectionResponse(): void
    {
        $response = new CollectionResponse(['some' => 'data']);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"some":"data"}', $response->getContent());
        $this->assertSame('application/json; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('UTF-8', $response->getCharset());
    }
}
