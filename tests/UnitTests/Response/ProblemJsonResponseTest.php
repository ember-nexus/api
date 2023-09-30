<?php

namespace App\tests\UnitTests\Response;

use App\Response\ProblemJsonResponse;
use PHPUnit\Framework\TestCase;

class ProblemJsonResponseTest extends TestCase
{
    public function testEmptyProblemJsonResponse(): void
    {
        $response = new ProblemJsonResponse();
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('{}', $response->getContent());
        $this->assertSame('application/problem+json; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('UTF-8', $response->getCharset());
    }

    public function testBasicProblemJsonResponse(): void
    {
        $response = new ProblemJsonResponse(['some' => 'data']);
        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('{"some":"data"}', $response->getContent());
        $this->assertSame('application/problem+json; charset=utf-8', $response->headers->get('Content-Type'));
        $this->assertSame('UTF-8', $response->getCharset());
    }
}
