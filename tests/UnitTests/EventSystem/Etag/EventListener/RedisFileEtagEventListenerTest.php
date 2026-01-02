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
use Beste\Psr\Log\TestLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(RedisFileEtagEventListener::class)]
class RedisFileEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;
    use AssertLoggerTrait;

    public function testRedisFileEtagEventListenerWithFileNotInRedis(): void
    {
        // setup variables
        $id = Uuid::fromString('3079fc16-9bd0-4b06-8cdf-b0b6cc16c307');
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
            'elementId' => '3079fc16-9bd0-4b06-8cdf-b0b6cc16c307',
            'redisKey' => 'etag:file:3079fc16-9bd0-4b06-8cdf-b0b6cc16c307',
        ]);
        $this->assertLogHappened($logger, 'debug', 'Unable to find Etag for file in Redis.', [
            'elementId' => '3079fc16-9bd0-4b06-8cdf-b0b6cc16c307',
            'redisKey' => 'etag:file:3079fc16-9bd0-4b06-8cdf-b0b6cc16c307',
        ]);
    }

    public function testRedisFileEtagEventListenerWithFileInRedisWithNullValue(): void
    {
        // setup variables
        $id = Uuid::fromString('0eaa9823-3131-448d-b8cd-122b5eada7a5');
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
            'elementId' => '0eaa9823-3131-448d-b8cd-122b5eada7a5',
            'redisKey' => 'etag:file:0eaa9823-3131-448d-b8cd-122b5eada7a5',
        ]);
        $this->assertLogHappened($logger, 'debug', 'Found Etag for file in Redis.', [
            'elementId' => '0eaa9823-3131-448d-b8cd-122b5eada7a5',
            'redisKey' => 'etag:file:0eaa9823-3131-448d-b8cd-122b5eada7a5',
            'etag' => null,
        ]);
    }

    public function testRedisFileEtagEventListenerWithFileInRedisWithValue(): void
    {
        // setup variables
        $id = Uuid::fromString('e0350f14-2aa0-4878-83a8-188dae3c3d93');
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
            'elementId' => 'e0350f14-2aa0-4878-83a8-188dae3c3d93',
            'redisKey' => 'etag:file:e0350f14-2aa0-4878-83a8-188dae3c3d93',
        ]);
        $data = $this->assertLogHappened($logger, 'debug', 'Found Etag for file in Redis.', false);
        $this->assertSame('e0350f14-2aa0-4878-83a8-188dae3c3d93', $data['elementId']);
        $this->assertSame('etag:file:e0350f14-2aa0-4878-83a8-188dae3c3d93', $data['redisKey']);
        $this->assertSame('someEtag', (string) $data['etag']);
    }
}
