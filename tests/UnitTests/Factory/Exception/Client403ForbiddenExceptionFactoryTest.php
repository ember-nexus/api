<?php

namespace App\Tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client403ForbiddenExceptionFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client403ForbiddenExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplate(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client403ForbiddenExceptionFactory($urlGenerator->reveal());

        $exception = $factory->createFromTemplate();

        $this->assertSame(403, $exception->getStatus());
        $this->assertSame('Forbidden', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('Requested endpoint, element or action is forbidden.', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }
}
