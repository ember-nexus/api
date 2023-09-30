<?php

namespace App\Tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Server503ServiceUnavailableExceptionFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Server503ServiceUnavailableExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplateInProductionEnvironment(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get(Argument::is('kernel.environment'))->shouldBeCalledOnce()->willReturn('prod');
        $factory = new Server503ServiceUnavailableExceptionFactory($urlGenerator->reveal(), $bag->reveal());

        $exception = $factory->createFromTemplate('a');

        $this->assertSame(503, $exception->getStatus());
        $this->assertSame('Service unavailable', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('The service itself or an internal component is currently unavailable.', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }

    public function testCreateFromTemplateInDevelopmentEnvironment(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get(Argument::is('kernel.environment'))->shouldBeCalledOnce()->willReturn('dev');
        $factory = new Server503ServiceUnavailableExceptionFactory($urlGenerator->reveal(), $bag->reveal());

        $exception = $factory->createFromTemplate('a');

        $this->assertSame(503, $exception->getStatus());
        $this->assertSame('Service unavailable', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame("Service 'a' is currently unavailable.", $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }
}
