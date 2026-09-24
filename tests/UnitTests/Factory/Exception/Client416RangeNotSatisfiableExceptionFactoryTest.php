<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client416RangeNotSatisfiableExceptionFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(Client416RangeNotSatisfiableExceptionFactory::class)]
class Client416RangeNotSatisfiableExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromDetail(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is([
                    'code' => '416',
                    'name' => 'range-not-satisfiable',
                ]),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/416');
        $factory = new Client416RangeNotSatisfiableExceptionFactory($urlGenerator->reveal());

        $exception = $factory->createFromDetail('some exception detail');

        $this->assertSame(416, $exception->getStatus());
        $this->assertSame('Range Not Satisfiable', $exception->getTitle());
        $this->assertSame('https://mock.dev/416', $exception->getType());
        $this->assertSame('some exception detail', $exception->getDetail());
        $this->assertNull($exception->getInstance());
        $this->assertSame('', $exception->getMessage());
        $this->assertNull($exception->getPrevious());
        $this->assertSame([], $exception->getAdditionalProperties());
    }

    public function testCreateFromDetailWithAdditionalProperties(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(Argument::cetera())
            ->willReturn('https://mock.dev/416');
        $factory = new Client416RangeNotSatisfiableExceptionFactory($urlGenerator->reveal());

        $exception = $factory->createFromDetail('some detail', ['range' => 'bytes=0-10']);

        $this->assertSame(['range' => 'bytes=0-10'], $exception->getAdditionalProperties());
    }
}
