<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client412PreconditionFailedExceptionFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client412PreconditionFailedExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplate(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client412PreconditionFailedExceptionFactory($urlGenerator->reveal());

        $exception = $factory->createFromTemplate();

        $this->assertSame(412, $exception->getStatus());
        $this->assertSame('Precondition Failed', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('Precondition does not match.', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }
}
