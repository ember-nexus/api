<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Factory\Type\RedisKeyFactory;
use App\Service\FileCreationLockService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(FileCreationLockService::class)]
class FileCreationLockServiceTest extends TestCase
{
    use ProphecyTrait;

    public function testAcquireSetsKeyWithNxAndTtlAndReturnsToken(): void
    {
        $elementId = Uuid::uuid4();
        $redis = $this->prophesize(Client::class);
        $redis->set(
            'file:create:'.$elementId->toString(),
            Argument::that(fn ($token) => is_string($token) && 32 === strlen($token)),
            'PX',
            900000,
            'NX'
        )->shouldBeCalledOnce()->willReturn('OK');

        $this->assertIsString((new FileCreationLockService($redis->reveal(), new RedisKeyFactory()))->acquire($elementId));
    }

    public function testAcquireReturnsNullIfAlreadyLocked(): void
    {
        $redis = $this->prophesize(Client::class);
        $redis->set(Argument::cetera())->willReturn(null);

        $this->assertNull((new FileCreationLockService($redis->reveal(), new RedisKeyFactory()))->acquire(Uuid::uuid4()));
    }

    #[DataProvider('isLockedProvider')]
    public function testIsLocked(int $existsResult, bool $expected): void
    {
        $elementId = Uuid::uuid4();
        $redis = $this->prophesize(Client::class);
        $redis->exists('file:create:'.$elementId->toString())->shouldBeCalledOnce()->willReturn($existsResult);

        $this->assertSame($expected, (new FileCreationLockService($redis->reveal(), new RedisKeyFactory()))->isLocked($elementId));
    }

    /**
     * @return array<string, array{0: int, 1: bool}>
     */
    public static function isLockedProvider(): array
    {
        return ['locked' => [1, true], 'not locked' => [0, false]];
    }

    public function testReleaseComparesTokenBeforeDeleting(): void
    {
        $elementId = Uuid::uuid4();
        $redis = $this->prophesize(Client::class);
        $redis->eval(
            Argument::that(fn ($script) => str_contains($script, 'get') && str_contains($script, 'del')),
            1,
            'file:create:'.$elementId->toString(),
            'my-token'
        )->shouldBeCalledOnce()->willReturn(1);

        (new FileCreationLockService($redis->reveal(), new RedisKeyFactory()))->release($elementId, 'my-token');
    }
}
