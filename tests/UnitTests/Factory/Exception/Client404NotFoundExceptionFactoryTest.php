<?php

namespace App\tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client404NotFoundExceptionFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client404NotFoundExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplate(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client404NotFoundExceptionFactory($urlGenerator->reveal());

        $exception = $factory->createFromTemplate();

        $this->assertSame(404, $exception->getStatus());
        $this->assertSame('Not found', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('Requested element was not found.', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }
}
