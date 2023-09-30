<?php

namespace App\Tests\UnitTests\Factory\Exception;

use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Client400IncompleteMutualDependencyExceptionFactory;
use App\Factory\Exception\Server500LogicExceptionFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client400IncompleteMutualDependencyExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testFailWithLessThanTwoProperties(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }
        $expectedException = new Server500LogicErrorException('');
        $serverExceptionFactory = $this->prophesize(Server500LogicExceptionFactory::class);
        $serverExceptionFactory
            ->createFromTemplate(Argument::is('Mutual dependency requires at least two properties.'))
            ->shouldBeCalledOnce()
            ->willReturn($expectedException);
        $factory = new Client400IncompleteMutualDependencyExceptionFactory(
            $this->prophesize(UrlGeneratorInterface::class)->reveal(),
            $serverExceptionFactory->reveal()
        );
        $this->expectExceptionObject($expectedException);
        $factory->createFromTemplate([], [], []);
    }

    public function testFailWithNoMissingProperty(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }
        $expectedException = new Server500LogicErrorException('');
        $serverExceptionFactory = $this->prophesize(Server500LogicExceptionFactory::class);
        $serverExceptionFactory
            ->createFromTemplate(Argument::is('At least one missing property is required.'))
            ->shouldBeCalledOnce()
            ->willReturn($expectedException);
        $factory = new Client400IncompleteMutualDependencyExceptionFactory(
            $this->prophesize(UrlGeneratorInterface::class)->reveal(),
            $serverExceptionFactory->reveal()
        );
        $this->expectExceptionObject($expectedException);
        $factory->createFromTemplate(['a', 'b'], [], []);
    }

    public function testSuccessWithNoSetProperty(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client400IncompleteMutualDependencyExceptionFactory(
            $urlGenerator->reveal(),
            $this->prophesize(Server500LogicExceptionFactory::class)->reveal()
        );

        $exception = $factory->createFromTemplate(['a', 'b'], [], ['a']);

        $this->assertSame(400, $exception->getStatus());
        $this->assertSame('Incomplete mutual dependency', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame("Endpoint has mutual dependency on properties 'a' and 'b'. While no property is set, property 'a' is missing.", $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }

    public function testSuccessWithOneSetProperty(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client400IncompleteMutualDependencyExceptionFactory(
            $urlGenerator->reveal(),
            $this->prophesize(Server500LogicExceptionFactory::class)->reveal()
        );

        $exception = $factory->createFromTemplate(['a', 'b'], ['b'], ['a']);

        $this->assertSame(400, $exception->getStatus());
        $this->assertSame('Incomplete mutual dependency', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame("Endpoint has mutual dependency on properties 'a' and 'b'. While property 'b' is set, property 'a' is missing.", $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }

    public function testSuccessWithMultipleSetProperties(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client400IncompleteMutualDependencyExceptionFactory(
            $urlGenerator->reveal(),
            $this->prophesize(Server500LogicExceptionFactory::class)->reveal()
        );

        $exception = $factory->createFromTemplate(['a', 'b', 'c'], ['b', 'c'], ['a']);

        $this->assertSame(400, $exception->getStatus());
        $this->assertSame('Incomplete mutual dependency', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame("Endpoint has mutual dependency on properties 'a', 'b' and 'c'. While properties 'b' and 'c' are set, property 'a' is missing.", $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }

    public function testSuccessWithSingleMissingProperty(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client400IncompleteMutualDependencyExceptionFactory(
            $urlGenerator->reveal(),
            $this->prophesize(Server500LogicExceptionFactory::class)->reveal()
        );

        $exception = $factory->createFromTemplate(['a', 'b', 'c'], [], ['a']);

        $this->assertSame(400, $exception->getStatus());
        $this->assertSame('Incomplete mutual dependency', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame("Endpoint has mutual dependency on properties 'a', 'b' and 'c'. While no property is set, property 'a' is missing.", $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }

    public function testSuccessWithMultipleMissingProperties(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client400IncompleteMutualDependencyExceptionFactory(
            $urlGenerator->reveal(),
            $this->prophesize(Server500LogicExceptionFactory::class)->reveal()
        );

        $exception = $factory->createFromTemplate(['a', 'b', 'c'], [], ['a', 'b']);

        $this->assertSame(400, $exception->getStatus());
        $this->assertSame('Incomplete mutual dependency', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame("Endpoint has mutual dependency on properties 'a', 'b' and 'c'. While no property is set, properties 'a' and 'b' are missing.", $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }
}
