<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Factory\Type\RedisKeyFactory;
use App\Service\FileCreationLockService;
use App\Service\RequestIdService;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(FileCreationLockService::class)]
class FileCreationLockServiceTest extends TestCase
{
    use ProphecyTrait;

    private const string REQUEST_ID = '02IiNr0G8viOVKjn71oeH0BK5KarxzLjlnY4f';

    private function buildRequestIdService(string $requestId = self::REQUEST_ID): RequestIdService
    {
        $requestIdService = $this->prophesize(RequestIdService::class);
        $requestIdService->getRequestId()->willReturn(Uuid::fromString($requestId));

        return $requestIdService->reveal();
    }

    public function testAcquireSetsKeyWithNxAndTtlAndReturnsTheRequestIdAsToken(): void
    {
        $elementId = Uuid::generate();
        $redis = $this->prophesize(Client::class);
        $redis->set(
            'lock:file-creation:'.$elementId->toString(),
            self::REQUEST_ID,
            'PX',
            900000,
            'NX'
        )->shouldBeCalledOnce()->willReturn('OK');

        $token = (new FileCreationLockService($redis->reveal(), new RedisKeyFactory(), $this->buildRequestIdService()))->acquire($elementId);

        $this->assertSame(self::REQUEST_ID, $token);
    }

    public function testAcquireReturnsNullIfAlreadyLocked(): void
    {
        $redis = $this->prophesize(Client::class);
        $redis->set(Argument::cetera())->willReturn(null);

        $this->assertNull((new FileCreationLockService($redis->reveal(), new RedisKeyFactory(), $this->buildRequestIdService()))->acquire(Uuid::generate()));
    }

    #[DataProvider('isLockedProvider')]
    public function testIsLocked(int $existsResult, bool $expected): void
    {
        $elementId = Uuid::generate();
        $redis = $this->prophesize(Client::class);
        $redis->exists('lock:file-creation:'.$elementId->toString())->shouldBeCalledOnce()->willReturn($existsResult);

        $this->assertSame($expected, (new FileCreationLockService($redis->reveal(), new RedisKeyFactory(), $this->buildRequestIdService()))->isLocked($elementId));
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
        $elementId = Uuid::generate();
        $key = 'lock:file-creation:'.$elementId->toString();
        $redis = $this->prophesize(Client::class);
        $redis->get($key)->shouldBeCalledOnce()->willReturn('my-token');
        $redis->del([$key])->shouldBeCalledOnce();

        (new FileCreationLockService($redis->reveal(), new RedisKeyFactory(), $this->buildRequestIdService()))->release($elementId, 'my-token');
    }

    public function testReleaseDoesNotDeleteWhenTokenDoesNotMatch(): void
    {
        $elementId = Uuid::generate();
        $key = 'lock:file-creation:'.$elementId->toString();
        $redis = $this->prophesize(Client::class);
        $redis->get($key)->shouldBeCalledOnce()->willReturn('someone-elses-token');
        $redis->del(Argument::cetera())->shouldNotBeCalled();

        (new FileCreationLockService($redis->reveal(), new RedisKeyFactory(), $this->buildRequestIdService()))->release($elementId, 'my-token');
    }
}
