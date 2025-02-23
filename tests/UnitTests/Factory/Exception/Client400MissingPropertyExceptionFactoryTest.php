<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(Client400MissingPropertyExceptionFactory::class)]
class Client400MissingPropertyExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplate(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(
            Argument::is('exception-detail'),
            Argument::is([
                'code' => '400',
                'name' => 'missing-property',
            ]),
            Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
        )->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client400MissingPropertyExceptionFactory($urlGenerator->reveal());

        $exception = $factory->createFromTemplate('a', 'b');

        $this->assertSame(400, $exception->getStatus());
        $this->assertSame('Missing property', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame("Endpoint requires that the request contains property 'a' to be set to b.", $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }
}
