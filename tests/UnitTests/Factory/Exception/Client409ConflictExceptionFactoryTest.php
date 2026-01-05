<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client409ConflictExceptionFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(Client409ConflictExceptionFactory::class)]
class Client409ConflictExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplate(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate(
            Argument::is('exception-detail'),
            Argument::is([
                'code' => '409',
                'name' => 'conflict',
            ]),
            Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
        )->shouldBeCalledOnce()->willReturn('https://mock.dev/123');
        $factory = new Client409ConflictExceptionFactory($urlGenerator->reveal());

        $additionalDetails = [
            'a' => 'b',
        ];
        $exception = $factory->createFromDetail('some detail', $additionalDetails);

        $this->assertSame(409, $exception->getStatus());
        $this->assertSame('Conflict', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('some detail', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
        $this->assertSame($additionalDetails, $exception->getAdditionalProperties());
    }
}
