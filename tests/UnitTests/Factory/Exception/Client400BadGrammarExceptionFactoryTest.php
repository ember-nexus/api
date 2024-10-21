<?php

declare(strict_types=1);

namespace App\tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client400BadGrammarExceptionFactory;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(Client400BadGrammarExceptionFactory::class)]
class Client400BadGrammarExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromDetail(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is([
                    'code' => '400',
                    'name' => 'bad-grammar',
                ]),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $factory = new Client400BadGrammarExceptionFactory($urlGenerator->reveal());

        $previous = new Exception('some content');
        $exception = $factory->createFromDetail('some exception detail', $previous);

        $this->assertSame(400, $exception->getStatus());
        $this->assertSame('Bad grammar', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('some exception detail', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
