<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Server501NotImplementedExceptionFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(Server501NotImplementedExceptionFactory::class)]
class Server501NotImplementedExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplate(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(
            Argument::is('exception-detail'),
            Argument::is([
                'code' => '501',
                'name' => 'not-implemented',
            ]),
            Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
        )->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Server501NotImplementedExceptionFactory($urlGenerator->reveal());

        $exception = $factory->createFromTemplate();

        $this->assertSame(501, $exception->getStatus());
        $this->assertSame('Not implemented', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('Endpoint is currently not implemented.', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }
}
