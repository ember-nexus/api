<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

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
            'upload-lock:'.$uploadId->toString(),
            Argument::that(fn ($token) => is_string($token) && 32 === strlen($token)),
            'PX',
            UploadLockService::TTL_IN_MILLISECONDS,
            'NX'
        )->shouldBeCalledOnce()->willReturn('OK');

        $token = (new UploadLockService($redis->reveal()))->acquire($uploadId);

        $this->assertIsString($token);
    }

    public function testAcquireReturnsNullIfAlreadyLocked(): void
    {
        $redis = $this->prophesize(Client::class);
        $redis->set(Argument::cetera())->willReturn(null);

        $this->assertNull((new UploadLockService($redis->reveal()))->acquire(Uuid::uuid4()));
    }

    public function testReleaseComparesTokenBeforeDeleting(): void
    {
        $uploadId = Uuid::uuid4();
        $redis = $this->prophesize(Client::class);
        $redis->eval(
            Argument::that(fn ($script) => str_contains($script, 'get') && str_contains($script, 'del')),
            1,
            'upload-lock:'.$uploadId->toString(),
            'my-token'
        )->shouldBeCalledOnce()->willReturn(1);

        (new UploadLockService($redis->reveal()))->release($uploadId, 'my-token');
    }
}
