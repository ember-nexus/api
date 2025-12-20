<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Factory\Exception;

use App\Factory\Exception\Client400BadContentExceptionFactory;
use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;
use ReflectionMethod;
use Safe\Exceptions\JsonException;
use stdClass;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Small]
#[CoversClass(Client400BadContentExceptionFactory::class)]
class Client400BadContentExceptionFactoryTest extends TestCase
{
    use ProphecyTrait;

    public function testCreateFromTemplate(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is([
                    'code' => '400',
                    'name' => 'bad-content',
                ]),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $factory = new Client400BadContentExceptionFactory($urlGenerator->reveal());

        $exception = $factory->createFromTemplate('a', '\'b\'', 'c');

        $this->assertSame(400, $exception->getStatus());
        $this->assertSame('Bad content', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame("Endpoint expects property 'a' to be 'b', got string 'c'.", $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
    }

    public function testCreateFromDetail(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is([
                    'code' => '400',
                    'name' => 'bad-content',
                ]),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $factory = new Client400BadContentExceptionFactory($urlGenerator->reveal());

        $previous = new Exception('some content');
        $exception = $factory->createFromDetail('some exception detail', $previous);

        $this->assertSame(400, $exception->getStatus());
        $this->assertSame('Bad content', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('some exception detail', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testCreateFromJsonException(): void
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator
            ->generate(
                Argument::is('exception-detail'),
                Argument::is([
                    'code' => '400',
                    'name' => 'bad-content',
                ]),
                Argument::is(UrlGeneratorInterface::ABSOLUTE_URL)
            )
            ->shouldBeCalledOnce()
            ->willReturn('https://mock.dev/123');
        $factory = new Client400BadContentExceptionFactory($urlGenerator->reveal());

        $jsonException = new JsonException('message');

        $exception = $factory->createFromJsonException($jsonException);

        $this->assertSame(400, $exception->getStatus());
        $this->assertSame('Bad content', $exception->getTitle());
        $this->assertSame('https://mock.dev/123', $exception->getType());
        $this->assertSame('Unable to parse request as JSON. message.', $exception->getDetail());
        $this->assertSame(null, $exception->getInstance());
        $this->assertSame('', $exception->getMessage());
        $this->assertSame($jsonException, $exception->getPrevious());
    }

    public static function inputProvider(): array
    {
        return [
            [
                'lorem ipsum',
                "string 'lorem ipsum'",
            ],
            [
                'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet. Lore',
                "string 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea tak' (truncated)",
            ],
            [
                null,
                'null',
            ],
            [
                true,
                'bool true',
            ],
            [
                false,
                'bool false',
            ],
            [
                345235,
                'int 345235',
            ],
            [
                3.14,
                'float 3.14',
            ],
            [
                [],
                'array with no elements',
            ],
            [
                ['a'],
                'array with one element',
            ],
            [
                ['a', 'b'],
                'array with 2 elements',
            ],
            [
                Uuid::fromString('7fff27c5-3147-4a77-9b79-c6b6876c6b52'),
                "'7fff27c5-3147-4a77-9b79-c6b6876c6b52' of type Ramsey\Uuid\Lazy\LazyUuidFromString",
            ],
            [
                new stdClass(),
                'type stdClass',
            ],
        ];
    }

    #[DataProvider('inputProvider')]
    public function testGetContentSummary($input, $output): void
    {
        $factory = new Client400BadContentExceptionFactory($this->prophesize(UrlGeneratorInterface::class)->reveal());
        $method = new ReflectionMethod(Client400BadContentExceptionFactory::class, 'getContentSummary');

        $result = $method->invokeArgs($factory, [$input]);
        $this->assertSame($result, $output);
    }
}
