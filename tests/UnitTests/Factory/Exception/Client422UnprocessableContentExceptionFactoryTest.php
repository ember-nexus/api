<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client422UnprocessableContentExceptionFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(Client422UnprocessableContentExceptionFactory::class)]
class Client422UnprocessableContentExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    private function buildFactory(): Client422UnprocessableContentExceptionFactory
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(
            Argument::is('exception-detail'),
            Argument::is([
                'code' => '422',
                'name' => 'unprocessable-content',
            ]),
            Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
        )->willReturn('https://mock.dev/123');

        return new Client422UnprocessableContentExceptionFactory($urlGenerator->reveal());
    }

    public function testCreateFromDetail(): void
    {
        $exception = $this->buildFactory()->createFromDetail('Some detail.');

        $this->assertSame(422, $exception->getStatus());
        $this->assertSame('Unprocessable content', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('Some detail.', $exception->getDetail());
        $this->assertNull($exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }

    public function testCreateFromExpressionTimeout(): void
    {
        $exception = $this->buildFactory()->createFromExpressionTimeout(5000);

        $this->assertSame(422, $exception->getStatus());
        $this->assertSame('Query execution timed out after 5000 ms.', $exception->getDetail());
    }
}
