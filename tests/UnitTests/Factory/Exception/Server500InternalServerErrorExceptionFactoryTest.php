<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Server500InternalServerErrorExceptionFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(Server500InternalServerErrorExceptionFactory::class)]
class Server500InternalServerErrorExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplateInProductionEnvironment(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(
            Argument::is('exception-detail'),
            Argument::is([
                'code' => '500',
                'name' => 'internal-server-error',
            ]),
            Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
        )->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get(Argument::is('kernel.environment'))->shouldBeCalledOnce()->willReturn('prod');
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(Argument::is('a'), Argument::is([]))->shouldBeCalledOnce();
        $factory = new Server500InternalServerErrorExceptionFactory($urlGenerator->reveal(), $bag->reveal(), $logger->reveal());

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
        $urlGenerator->generate(
            Argument::is('exception-detail'),
            Argument::is([
                'code' => '500',
                'name' => 'internal-server-error',
            ]),
            Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
        )->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get(Argument::is('kernel.environment'))->shouldBeCalledOnce()->willReturn('dev');
        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(Argument::is('a'), Argument::is([]))->shouldBeCalledOnce();
        $factory = new Server500InternalServerErrorExceptionFactory($urlGenerator->reveal(), $bag->reveal(), $logger->reveal());

        $exception = $factory->createFromTemplate('a');

        $this->assertSame(500, $exception->getStatus());
        $this->assertSame('Internal server error', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('a', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }
}
