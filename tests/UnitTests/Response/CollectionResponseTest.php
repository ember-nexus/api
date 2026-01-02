<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Response;

use App\Type\Etag;
use App\Type\Response\CollectionResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(CollectionResponse::class)]
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

    public function testSetEtagFromEtagInstance(): void
    {
        $response = new CollectionResponse();
        $this->assertNull($response->getEtag());
        $etag = new Etag('some etag');
        $response->setEtagFromEtagInstance($etag);
        $this->assertSame('"some etag"', $response->getEtag());
    }
}
