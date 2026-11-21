<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Security;

use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Security\AuthProvider;
use App\Security\TokenGenerator;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Small]
#[CoversClass(AuthProvider::class)]
class AuthProviderTest extends TestCase
{
    use ProphecyTrait;

    public function testFailWhenAnonymousUserIsNotConfigured(): void
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get(Argument::is('anonymousUserUUID'))->shouldBeCalledOnce()->willReturn(null);

        $serverException = new Server500LogicErrorException('');

        $serverExceptionFactory = $this->prophesize(Server500LogicErrorExceptionFactory::class);
        $serverExceptionFactory
            ->createFromTemplate(Argument::is('anonymousUserUUID must be set to a valid UUID'))
            ->shouldBeCalledOnce()
            ->willReturn($serverException);

        $this->expectExceptionObject($serverException);

        new AuthProvider(
            $bag->reveal(),
            $this->prophesize(TokenGenerator::class)->reveal(),
            $serverExceptionFactory->reveal()
        );
    }

    public function testInitialState(): void
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get(Argument::is('anonymousUserUUID'))->shouldBeCalledOnce()->willReturn('02IiNr0G8viOVKjSzqHBHo1qzjnXu7l1Hdocz');
        $authProvider = new AuthProvider(
            $bag->reveal(),
            $this->prophesize(TokenGenerator::class)->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $this->assertTrue($authProvider->isAnonymous());
        $this->assertSame('02IiNr0G8viOVKjSzqHBHo1qzjnXu7l1Hdocz', $authProvider->getUserId()->toString());
        $this->assertNull($authProvider->getHashedToken());
        $this->assertNull($authProvider->getTokenId());
    }

    public function testSetUserAndToken(): void
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get(Argument::is('anonymousUserUUID'))->shouldBeCalledOnce()->willReturn('02IiNr0G8viOVKjSzqHBHo1qzjnXu7l1Hdocz');
        $authProvider = new AuthProvider(
            $bag->reveal(),
            $this->prophesize(TokenGenerator::class)->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $authProvider->setUserAndToken(Uuid::fromString('02IiNr0G8viOVKl0NVnAXbLPSIUrELTd4k3AC'));

        $this->assertFalse($authProvider->isAnonymous());
        $this->assertSame($authProvider->getUserId()->toString(), '02IiNr0G8viOVKl0NVnAXbLPSIUrELTd4k3AC');
        $this->assertNull($authProvider->getHashedToken());
        $this->assertNull($authProvider->getTokenId());

        $authProvider->setUserAndToken(
            Uuid::fromString('02IiNr0G8viOVKkNuOQlIFMNqh2bOiN2KMJpC'),
            Uuid::fromString('02IiNr0G8viOVKkQg8CVOBaX40LNaGTOY7YcB'),
            'some hashed token'
        );

        $this->assertFalse($authProvider->isAnonymous());
        $this->assertSame('02IiNr0G8viOVKkNuOQlIFMNqh2bOiN2KMJpC', $authProvider->getUserId()->toString());
        $this->assertSame('some hashed token', $authProvider->getHashedToken());
        $this->assertSame('02IiNr0G8viOVKkQg8CVOBaX40LNaGTOY7YcB', $authProvider->getTokenId()->toString());

        $authProvider->setUserAndToken(Uuid::fromString('02IiNr0G8viOVKkstOC9iWUFCWcdEAkfOcFXo'), null, null, true);

        $this->assertTrue($authProvider->isAnonymous());
        $this->assertSame($authProvider->getUserId()->toString(), '02IiNr0G8viOVKkstOC9iWUFCWcdEAkfOcFXo');
        $this->assertNull($authProvider->getHashedToken());
        $this->assertNull($authProvider->getTokenId());
    }

    public function testRedisToken(): void
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get(Argument::is('anonymousUserUUID'))->shouldBeCalledOnce()->willReturn('02IiNr0G8viOVKjSzqHBHo1qzjnXu7l1Hdocz');
        $tokenGenerator = $this->prophesize(TokenGenerator::class);
        $tokenGenerator
            ->hashToken(Argument::is('test'))
            ->shouldBeCalledOnce()
            ->willReturn('hashed test');
        $authProvider = new AuthProvider(
            $bag->reveal(),
            $tokenGenerator->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal()
        );

        $redisTokenKeyFromHashedToken = $authProvider->getRedisTokenKeyFromHashedToken('test');
        $this->assertSame('token:test', $redisTokenKeyFromHashedToken);

        $redisTokenKeyFromRawToken = $authProvider->getRedisTokenKeyFromRawToken('test');
        $this->assertSame('token:hashed test', $redisTokenKeyFromRawToken);
    }
}
