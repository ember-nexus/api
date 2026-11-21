<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Factory\Type\RedisKeyFactory;
use App\Service\MaintenanceLockService;
use App\Type\MaintenanceLockType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(MaintenanceLockService::class)]
class MaintenanceLockServiceTest extends TestCase
{
    use ProphecyTrait;

    private const string KEY = 'lock:maintenance:mphf';

    public function testClaimSetsKeyWithTtlAndNx(): void
    {
        $redis = $this->prophesize(Client::class);
        $redis->set(self::KEY, Argument::type('string'), 'EX', 100, 'NX')->shouldBeCalledOnce()->willReturn('OK');
        $service = new MaintenanceLockService($redis->reveal(), new RedisKeyFactory(), 100);

        $lock = $service->claim(MaintenanceLockType::MPHF);

        $this->assertNotNull($lock);
        $this->assertSame(MaintenanceLockType::MPHF, $lock->getType());
        $this->assertSame(32, strlen($lock->getToken()));
    }

    public function testClaimReturnsNullIfTaken(): void
    {
        $redis = $this->prophesize(Client::class);
        $redis->set(self::KEY, Argument::cetera())->willReturn(null);
        $service = new MaintenanceLockService($redis->reveal(), new RedisKeyFactory(), 100);

        $this->assertNull($service->claim(MaintenanceLockType::MPHF));
    }

    public function testReleaseReportsOwnership(): void
    {
        $redis = $this->prophesize(Client::class);
        $redis->set(Argument::cetera())->willReturn('OK');
        $redis->eval(Argument::type('string'), 1, self::KEY, Argument::type('string'))->willReturn(1, 0);
        $service = new MaintenanceLockService($redis->reveal(), new RedisKeyFactory(), 100);
        $lock = $service->claim(MaintenanceLockType::MPHF);
        $this->assertNotNull($lock);

        $this->assertTrue($service->release($lock));
        $this->assertFalse($service->release($lock));
    }

    public function testRefreshPassesTtl(): void
    {
        $redis = $this->prophesize(Client::class);
        $redis->set(Argument::cetera())->willReturn('OK');
        $redis->eval(Argument::type('string'), 1, self::KEY, Argument::type('string'), '100')->shouldBeCalledOnce()->willReturn(1);
        $service = new MaintenanceLockService($redis->reveal(), new RedisKeyFactory(), 100);
        $lock = $service->claim(MaintenanceLockType::MPHF);
        $this->assertNotNull($lock);

        $this->assertTrue($service->refresh($lock));
    }

    public function testIsStillOwnedAndIsHeld(): void
    {
        $redis = $this->prophesize(Client::class);
        $redis->set(Argument::cetera())->willReturn('OK');
        $service = new MaintenanceLockService($redis->reveal(), new RedisKeyFactory(), 100);
        $lock = $service->claim(MaintenanceLockType::MPHF);
        $this->assertNotNull($lock);
        $redis->get(self::KEY)->willReturn($lock->getToken(), 'someone-else');
        $redis->exists(self::KEY)->willReturn(1);
        $redis->exists('lock:maintenance:database')->willReturn(0);

        $this->assertTrue($service->isStillOwned($lock));
        $this->assertFalse($service->isStillOwned($lock));
        $this->assertTrue($service->isHeld(MaintenanceLockType::MPHF));
        $this->assertFalse($service->isHeld(MaintenanceLockType::DATABASE));
    }

    public function testReleaseAllHeldReleasesClaimedLocks(): void
    {
        $redis = $this->prophesize(Client::class);
        $redis->set(Argument::cetera())->willReturn('OK');
        $redis->eval(Argument::cetera())->shouldBeCalledTimes(2)->willReturn(1);
        $service = new MaintenanceLockService($redis->reveal(), new RedisKeyFactory(), 100);
        $service->claim(MaintenanceLockType::MPHF);
        $service->claim(MaintenanceLockType::DATABASE);

        $service->releaseAllHeld();
        $service->releaseAllHeld();
    }
}
