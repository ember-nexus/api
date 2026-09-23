<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client408RequestTimeoutExceptionFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(Client408RequestTimeoutExceptionFactory::class)]
class Client408RequestTimeoutExceptionFactoryTest extends TestCase
{
    private function buildFactory(): Client408RequestTimeoutExceptionFactory
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects($this->once())
            ->method('generate')
            ->with('exception-detail', ['code' => '408', 'name' => 'request-timeout'], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('http://example.com/error/408/request-timeout');

        return new Client408RequestTimeoutExceptionFactory($urlGenerator);
    }

    public function testCreateFromDetail(): void
    {
        $exception = $this->buildFactory()->createFromDetail('Some detail.');

        $this->assertSame('http://example.com/error/408/request-timeout', $exception->getType());
        $this->assertSame('Request timeout', $exception->getTitle());
        $this->assertSame(408, $exception->getStatus());
        $this->assertSame('Some detail.', $exception->getDetail());
    }

    public function testCreateFromIncompleteBody(): void
    {
        $exception = $this->buildFactory()->createFromIncompleteBody(5, 10);

        $this->assertStringContainsString('received 5 of 10 bytes', $exception->getDetail());
        $this->assertStringContainsString('time limit of the server', $exception->getDetail());
    }
}
