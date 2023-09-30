<?php

namespace App\Tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client405MethodNotAllowedExceptionFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client405MethodNotAllowedExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplate(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client405MethodNotAllowedExceptionFactory($urlGenerator->reveal());

        $exception = $factory->createFromTemplate();

        $this->assertSame(405, $exception->getStatus());
        $this->assertSame('Method not allowed', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('Endpoint does not support requested method, or you do not have sufficient permissions.', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }
}
