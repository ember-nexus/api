<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Safe\Exceptions\JsonException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client400BadContentExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplate(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client400BadContentExceptionFactory($urlGenerator->reveal());

        $exception = $factory->createFromTemplate('a', 'b', 'c');

        $this->assertSame(400, $exception->getStatus());
        $this->assertSame('Bad content', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame("Endpoint expects property 'a' to be b, got 'c'.", $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }

    public function testCreateFromJsonException(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client400BadContentExceptionFactory($urlGenerator->reveal());

        $jsonException = new JsonException('message');

        $exception = $factory->createFromJsonException($jsonException);

        $this->assertSame(400, $exception->getStatus());
        $this->assertSame('Bad content', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('Unable to parse request as JSON. message.', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
        $this->assertSame($jsonException, $exception->getPrevious());
    }
}
