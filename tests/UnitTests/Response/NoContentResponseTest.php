<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Response;

use App\Response\NoContentResponse;
use PHPUnit\Framework\TestCase;

class NoContentResponseTest extends TestCase
{
    public function testNoContentResponse(): void
    {
        $response = new NoContentResponse();
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
        $this->assertFalse($response->headers->has('Content-Type'));
    }
}
