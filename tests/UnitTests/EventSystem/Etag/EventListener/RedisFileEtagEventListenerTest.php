<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\FileEtagEvent;
use App\EventSystem\Etag\EventListener\RedisFileEtagEventListener;
use App\Factory\Type\RedisKeyFactory;
use App\Tests\UnitTests\AssertLoggerTrait;
use App\Type\Etag;
use App\Type\RedisKey;
use App\Type\RedisPrefixType;
use App\Type\RedisValueType;
use App\Type\Uuid;
use Beste\Psr\Log\TestLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(RedisFileEtagEventListener::class)]
class RedisFileEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;
    use AssertLoggerTrait;

    public function testRedisFileEtagEventListenerWithFileNotInRedis(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjin93HzGc5SD8UKuKuXFHey');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_FILE, $id->toString());
        $fileEtagEvent = new FileEtagEvent($id);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagFileRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisFileEtagEventListener = new RedisFileEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisFileEtagEventListener->onFileEtagEvent($fileEtagEvent);

        // assert event
        $this->assertFalse($fileEtagEvent->isPropagationStopped());
        $this->assertNull($fileEtagEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for file in Redis.', [
            'elementId' => '02IiNr0G8viOVKjin93HzGc5SD8UKuKuXFHey',
            'redisKey' => 'etag:file:02IiNr0G8viOVKjin93HzGc5SD8UKuKuXFHey',
        ]);
        $this->assertLogHappened($logger, 'debug', 'Unable to find Etag for file in Redis.', [
            'elementId' => '02IiNr0G8viOVKjin93HzGc5SD8UKuKuXFHey',
            'redisKey' => 'etag:file:02IiNr0G8viOVKjin93HzGc5SD8UKuKuXFHey',
        ]);
    }

    public function testRedisFileEtagEventListenerWithFileInRedisWithNullValue(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKjSKITveOu20CmRvKrfzh0Zt');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_FILE, $id->toString());
        $fileEtagEvent = new FileEtagEvent($id);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn(RedisValueType::NULL->value);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagFileRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisFileEtagEventListener = new RedisFileEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisFileEtagEventListener->onFileEtagEvent($fileEtagEvent);

        // assert event
        $this->assertTrue($fileEtagEvent->isPropagationStopped());
        $this->assertNull($fileEtagEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for file in Redis.', [
            'elementId' => '02IiNr0G8viOVKjSKITveOu20CmRvKrfzh0Zt',
            'redisKey' => 'etag:file:02IiNr0G8viOVKjSKITveOu20CmRvKrfzh0Zt',
        ]);
        $this->assertLogHappened($logger, 'debug', 'Found Etag for file in Redis.', [
            'elementId' => '02IiNr0G8viOVKjSKITveOu20CmRvKrfzh0Zt',
            'redisKey' => 'etag:file:02IiNr0G8viOVKjSKITveOu20CmRvKrfzh0Zt',
            'etag' => null,
        ]);
    }

    public function testRedisFileEtagEventListenerWithFileInRedisWithValue(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKl6NHDycWcDYJLdyyhJ6724z');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_FILE, $id->toString());
        $fileEtagEvent = new FileEtagEvent($id);
        $etag = new Etag('someEtag');

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn((string) $etag);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagFileRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisFileEtagEventListener = new RedisFileEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisFileEtagEventListener->onFileEtagEvent($fileEtagEvent);

        // assert event
        $this->assertTrue($fileEtagEvent->isPropagationStopped());
        $this->assertSame('someEtag', (string) $fileEtagEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for file in Redis.', [
            'elementId' => '02IiNr0G8viOVKl6NHDycWcDYJLdyyhJ6724z',
            'redisKey' => 'etag:file:02IiNr0G8viOVKl6NHDycWcDYJLdyyhJ6724z',
        ]);
        $data = $this->assertLogHappened($logger, 'debug', 'Found Etag for file in Redis.', false);
        $this->assertSame('02IiNr0G8viOVKl6NHDycWcDYJLdyyhJ6724z', $data['elementId']);
        $this->assertSame('etag:file:02IiNr0G8viOVKl6NHDycWcDYJLdyyhJ6724z', $data['redisKey']);
        $this->assertSame('someEtag', (string) $data['etag']);
    }
}
