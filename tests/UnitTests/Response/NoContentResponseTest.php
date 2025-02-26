<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Response;

use App\Response\NoContentResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(NoContentResponse::class)]
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
