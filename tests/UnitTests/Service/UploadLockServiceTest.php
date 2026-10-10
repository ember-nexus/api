<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Factory\Type\RedisKeyFactory;
use App\Service\UploadLockService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(UploadLockService::class)]
class UploadLockServiceTest extends TestCase
{
    use ProphecyTrait;

    public function testAcquireSetsKeyWithNxAndTtlAndReturnsToken(): void
    {
        $uploadId = Uuid::uuid4();
        $redis = $this->prophesize(Client::class);
        $redis->set(
            'lock:upload:'.$uploadId->toString(),
            Argument::that(fn ($token) => is_string($token) && 32 === strlen($token)),
            'PX',
            UploadLockService::TTL_IN_MILLISECONDS,
            'NX'
        )->shouldBeCalledOnce()->willReturn('OK');

        $token = (new UploadLockService($redis->reveal(), new RedisKeyFactory()))->acquire($uploadId);

        $this->assertIsString($token);
    }

    public function testAcquireReturnsNullIfAlreadyLocked(): void
    {
        $redis = $this->prophesize(Client::class);
        $redis->set(Argument::cetera())->willReturn(null);

        $this->assertNull((new UploadLockService($redis->reveal(), new RedisKeyFactory()))->acquire(Uuid::uuid4()));
    }

    public function testReleaseComparesTokenBeforeDeleting(): void
    {
        $uploadId = Uuid::uuid4();
        $key = 'lock:upload:'.$uploadId->toString();
        $redis = $this->prophesize(Client::class);
        $redis->get($key)->shouldBeCalledOnce()->willReturn('my-token');
        $redis->del([$key])->shouldBeCalledOnce();

        (new UploadLockService($redis->reveal(), new RedisKeyFactory()))->release($uploadId, 'my-token');
    }

    public function testReleaseDoesNotDeleteWhenTokenDoesNotMatch(): void
    {
        $uploadId = Uuid::uuid4();
        $key = 'lock:upload:'.$uploadId->toString();
        $redis = $this->prophesize(Client::class);
        $redis->get($key)->shouldBeCalledOnce()->willReturn('someone-elses-token');
        $redis->del(Argument::cetera())->shouldNotBeCalled();

        (new UploadLockService($redis->reveal(), new RedisKeyFactory()))->release($uploadId, 'my-token');
    }
}
