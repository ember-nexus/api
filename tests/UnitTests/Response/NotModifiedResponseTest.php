<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Response;

use App\Response\NotModifiedResponse;
use App\Type\Etag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(NotModifiedResponse::class)]
class NotModifiedResponseTest extends TestCase
{
    public function testNotModifiedResponse(): void
    {
        $response = new NotModifiedResponse();
        $this->assertSame(304, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
        $this->assertFalse($response->headers->has('Content-Type'));
        $this->assertNull($response->getEtag());
        $etag = new Etag('someEtag');
        $response->setEtagFromEtagInstance($etag);
        $this->assertSame('"someEtag"', $response->getEtag());
    }
}
