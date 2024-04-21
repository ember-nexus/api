<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client400ReservedIdentifierExceptionFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Client400ReservedIdentifierExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplate(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(Argument::cetera())->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client400ReservedIdentifierExceptionFactory($urlGenerator->reveal());

        $exception = $factory->createFromTemplate('a');

        $this->assertSame(400, $exception->getStatus());
        $this->assertSame('Reserved identifier', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame("The requested identifier 'a' is reserved and can not be used.", $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }
}
