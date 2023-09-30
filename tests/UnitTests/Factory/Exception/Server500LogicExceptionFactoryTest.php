<?php

namespace App\Tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Server500LogicExceptionFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Server500LogicExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplateInProductionEnvironment(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get(Argument::is('kernel.environment'))->shouldBeCalledOnce()->willReturn('prod');
        $factory = new Server500LogicExceptionFactory($urlGenerator->reveal(), $bag->reveal());

        $exception = $factory->createFromTemplate('a');

        $this->assertSame(500, $exception->getStatus());
        $this->assertSame('Internal server error', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('Internal server error, see log.', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }

    public function testCreateFromTemplateInDevelopmentEnvironment(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get(Argument::is('kernel.environment'))->shouldBeCalledOnce()->willReturn('dev');
        $factory = new Server500LogicExceptionFactory($urlGenerator->reveal(), $bag->reveal());

        $exception = $factory->createFromTemplate('a');

        $this->assertSame(500, $exception->getStatus());
        $this->assertSame('Internal server error', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('a', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }
}
