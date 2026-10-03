<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Factory\Type\RedisKeyFactory;
use App\Service\ExpiredUploadDeletionAttemptService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(ExpiredUploadDeletionAttemptService::class)]
class ExpiredUploadDeletionAttemptServiceTest extends TestCase
{
    use ProphecyTrait;

    private const string ID = 'abc';
    private const string KEY = 'cron:delete-expired-upload:abc';

    public function testUnknownUploadIsNotDeferred(): void
    {
        $redis = $this->prophesize(RedisClient::class);
        $redis->get(self::KEY)->willReturn(null);

        $this->assertFalse((new ExpiredUploadDeletionAttemptService($redis->reveal(), new RedisKeyFactory()))->isDeferred(self::ID));
    }

    public function testUploadIsDeferredUntilNotBefore(): void
    {
        $redis = $this->prophesize(RedisClient::class);
        $redis->get(self::KEY)->willReturn(json_encode(['attempts' => 1, 'notBefore' => time() + 100]));
        $service = new ExpiredUploadDeletionAttemptService($redis->reveal(), new RedisKeyFactory());
        $this->assertTrue($service->isDeferred(self::ID));

        $redis->get(self::KEY)->willReturn(json_encode(['attempts' => 1, 'notBefore' => time() - 1]));
        $this->assertFalse($service->isDeferred(self::ID));
    }

    public function testCorruptStateIsTreatedAsNoState(): void
    {
        $redis = $this->prophesize(RedisClient::class);
        $redis->get(self::KEY)->willReturn('garbage');

        $this->assertFalse((new ExpiredUploadDeletionAttemptService($redis->reveal(), new RedisKeyFactory()))->isDeferred(self::ID));
    }

    public function testFirstFailureDefersForOneHour(): void
    {
        $redis = $this->prophesize(RedisClient::class);
        $redis->get(self::KEY)->willReturn(null);
        $redis->set(
            self::KEY,
            Argument::that(function (string $json): bool {
                $data = json_decode($json, true);

                return 1 === $data['attempts'] && abs($data['notBefore'] - (time() + 3600)) <= 2;
            }),
            'EX',
            ExpiredUploadDeletionAttemptService::TTL_IN_SECONDS
        )->shouldBeCalledOnce();

        $this->assertSame(1, (new ExpiredUploadDeletionAttemptService($redis->reveal(), new RedisKeyFactory()))->recordFailure(self::ID));
    }

    public function testSecondFailureDefersForOneDay(): void
    {
        $redis = $this->prophesize(RedisClient::class);
        $redis->get(self::KEY)->willReturn(json_encode(['attempts' => 1, 'notBefore' => 0]));
        $redis->set(
            self::KEY,
            Argument::that(function (string $json): bool {
                $data = json_decode($json, true);

                return 2 === $data['attempts'] && abs($data['notBefore'] - (time() + 86400)) <= 2;
            }),
            'EX',
            ExpiredUploadDeletionAttemptService::TTL_IN_SECONDS
        )->shouldBeCalledOnce();

        $this->assertSame(2, (new ExpiredUploadDeletionAttemptService($redis->reveal(), new RedisKeyFactory()))->recordFailure(self::ID));
    }

    public function testThirdFailureIsFinal(): void
    {
        $redis = $this->prophesize(RedisClient::class);
        $redis->get(self::KEY)->willReturn(json_encode(['attempts' => 2, 'notBefore' => 0]));
        $redis->set(self::KEY, Argument::any(), 'EX', Argument::any())->shouldBeCalledOnce();

        $this->assertSame(
            ExpiredUploadDeletionAttemptService::MAX_ATTEMPTS,
            (new ExpiredUploadDeletionAttemptService($redis->reveal(), new RedisKeyFactory()))->recordFailure(self::ID)
        );
    }

    public function testClearDeletesKey(): void
    {
        $redis = $this->prophesize(RedisClient::class);
        $redis->del([self::KEY])->shouldBeCalledOnce();

        (new ExpiredUploadDeletionAttemptService($redis->reveal(), new RedisKeyFactory()))->clear(self::ID);
    }
}
