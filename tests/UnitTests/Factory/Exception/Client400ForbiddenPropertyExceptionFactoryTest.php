<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client400ForbiddenPropertyExceptionFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(Client400ForbiddenPropertyExceptionFactory::class)]
class Client400ForbiddenPropertyExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplate(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(
            Argument::is('exception-detail'),
            Argument::is([
                'code' => '400',
                'name' => 'forbidden-property',
            ]),
            Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
        )->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client400ForbiddenPropertyExceptionFactory($urlGenerator->reveal());

        $exception = $factory->createFromTemplate('a');

        $this->assertSame(400, $exception->getStatus());
        $this->assertSame('Forbidden property', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame("Endpoint does not accept setting the property 'a' in the request.", $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }
}
