<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Response;

use App\Response\CreatedResponse;
use PHPUnit\Framework\TestCase;

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
