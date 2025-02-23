<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Response;

use App\Response\CreatedResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(CreatedResponse::class)]
class CreatedResponseTest extends TestCase
{
    public function testCreatedResponseWithNoLocation(): void
    {
        $response = new CreatedResponse();
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
        $this->assertSame('', $response->headers->get('Location'));
        $this->assertFalse($response->headers->has('Content-Type'));
    }

    public function testCreatedResponseWithLocation(): void
    {
        $response = new CreatedResponse('/some-location');
        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
        $this->assertSame('/some-location', $response->headers->get('Location'));
        $this->assertFalse($response->headers->has('Content-Type'));
    }
}
