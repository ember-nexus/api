<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client429TooManyRequestsExceptionFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(Client429TooManyRequestsExceptionFactory::class)]
class Client429TooManyRequestsExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplate(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(
            Argument::is('exception-detail'),
            Argument::is([
                'code' => '429',
                'name' => 'too-many-requests',
            ]),
            Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
        )->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client429TooManyRequestsExceptionFactory($urlGenerator->reveal());

        $exception = $factory->createFromTemplate();

        $this->assertSame(429, $exception->getStatus());
        $this->assertSame('Too many requests', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('You have sent too many requests, please slow down.', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }
}
