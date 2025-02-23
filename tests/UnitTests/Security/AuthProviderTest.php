<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Security;

use App\Exception\Server500LogicErrorException;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Security\AuthProvider;
use App\Security\TokenGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\Uuid;
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

        $serverExceptionFactory = $this->prophesize(Server500LogicExceptionFactory::class);
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
        $bag->get(Argument::is('anonymousUserUUID'))->shouldBeCalledOnce()->willReturn('100ee3de-3846-4bd4-be4a-b17886dfc50c');
        $authProvider = new AuthProvider(
            $bag->reveal(),
            $this->prophesize(TokenGenerator::class)->reveal(),
            $this->prophesize(Server500LogicExceptionFactory::class)->reveal()
        );

        $this->assertTrue($authProvider->isAnonymous());
        $this->assertSame('100ee3de-3846-4bd4-be4a-b17886dfc50c', $authProvider->getUserId()->toString());
        $this->assertNull($authProvider->getHashedToken());
        $this->assertNull($authProvider->getTokenId());
    }

    public function testSetUserAndToken(): void
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get(Argument::is('anonymousUserUUID'))->shouldBeCalledOnce()->willReturn('100ee3de-3846-4bd4-be4a-b17886dfc50c');
        $authProvider = new AuthProvider(
            $bag->reveal(),
            $this->prophesize(TokenGenerator::class)->reveal(),
            $this->prophesize(Server500LogicExceptionFactory::class)->reveal()
        );

        $authProvider->setUserAndToken(Uuid::fromString('d3e0ce1e-cdf1-4c80-beae-266008d5e520'));

        $this->assertFalse($authProvider->isAnonymous());
        $this->assertSame($authProvider->getUserId()->toString(), 'd3e0ce1e-cdf1-4c80-beae-266008d5e520');
        $this->assertNull($authProvider->getHashedToken());
        $this->assertNull($authProvider->getTokenId());

        $authProvider->setUserAndToken(
            UuidV4::fromString('84e2dca1-3d54-4eda-81af-203c5cb4cec7'),
            UuidV4::fromString('8a961321-fd60-475a-95b4-7f976ce44213'),
            'some hashed token'
        );

        $this->assertFalse($authProvider->isAnonymous());
        $this->assertSame('84e2dca1-3d54-4eda-81af-203c5cb4cec7', $authProvider->getUserId()->toString());
        $this->assertSame('some hashed token', $authProvider->getHashedToken());
        $this->assertSame('8a961321-fd60-475a-95b4-7f976ce44213', $authProvider->getTokenId()->toString());

        $authProvider->setUserAndToken(Uuid::fromString('c48aaf8e-4ab5-4e60-8f03-154b13e35724'), null, null, true);

        $this->assertTrue($authProvider->isAnonymous());
        $this->assertSame($authProvider->getUserId()->toString(), 'c48aaf8e-4ab5-4e60-8f03-154b13e35724');
        $this->assertNull($authProvider->getHashedToken());
        $this->assertNull($authProvider->getTokenId());
    }

    public function testRedisToken(): void
    {
        $bag = $this->prophesize(ParameterBagInterface::class);
        $bag->get(Argument::is('anonymousUserUUID'))->shouldBeCalledOnce()->willReturn('100ee3de-3846-4bd4-be4a-b17886dfc50c');
        $tokenGenerator = $this->prophesize(TokenGenerator::class);
        $tokenGenerator
            ->hashToken(Argument::is('test'))
            ->shouldBeCalledOnce()
            ->willReturn('hashed test');
        $authProvider = new AuthProvider(
            $bag->reveal(),
            $tokenGenerator->reveal(),
            $this->prophesize(Server500LogicExceptionFactory::class)->reveal()
        );

        $redisTokenKeyFromHashedToken = $authProvider->getRedisTokenKeyFromHashedToken('test');
        $this->assertSame('token:test', $redisTokenKeyFromHashedToken);

        $redisTokenKeyFromRawToken = $authProvider->getRedisTokenKeyFromRawToken('test');
        $this->assertSame('token:hashed test', $redisTokenKeyFromRawToken);
    }
}
