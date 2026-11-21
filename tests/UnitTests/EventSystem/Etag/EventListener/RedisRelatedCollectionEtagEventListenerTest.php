<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
use App\EventSystem\Etag\EventListener\RedisRelatedCollectionEtagEventListener;
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
#[CoversClass(RedisRelatedCollectionEtagEventListener::class)]
class RedisRelatedCollectionEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;
    use AssertLoggerTrait;

    public function testRedisRelatedCollectionEtagEventListenerWithElementNotInRedis(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_RELATED_COLLECTION, $id->toString());
        $elementRelatedCollectionEvent = new RelatedCollectionEtagEvent($id);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagRelatedCollectionRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisRelatedCollectionEtagEventListener = new RedisRelatedCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisRelatedCollectionEtagEventListener->onRelatedCollectionEtagEvent($elementRelatedCollectionEvent);

        // assert event
        $this->assertFalse($elementRelatedCollectionEvent->isPropagationStopped());
        $this->assertNull($elementRelatedCollectionEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for related collection in Redis.', [
            'centerId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:related:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
        ]);
        $this->assertLogHappened($logger, 'debug', 'Unable to find Etag for related collection in Redis.', [
            'centerId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:related:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
        ]);
    }

    public function testRedisRelatedCollectionEtagEventListenerWithElementInRedisWithNullValue(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_RELATED_COLLECTION, $id->toString());
        $elementRelatedCollectionEvent = new RelatedCollectionEtagEvent($id);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn(RedisValueType::NULL->value);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagRelatedCollectionRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisRelatedCollectionEtagEventListener = new RedisRelatedCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisRelatedCollectionEtagEventListener->onRelatedCollectionEtagEvent($elementRelatedCollectionEvent);

        // assert event
        $this->assertTrue($elementRelatedCollectionEvent->isPropagationStopped());
        $this->assertNull($elementRelatedCollectionEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for related collection in Redis.', [
            'centerId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:related:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
        ]);
        $this->assertLogHappened($logger, 'debug', 'Found Etag for related collection in Redis.', [
            'centerId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:related:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'etag' => null,
        ]);
    }

    public function testRedisRelatedCollectionEtagEventListenerWithElementInRedisWithValue(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_RELATED_COLLECTION, $id->toString());
        $elementRelatedCollectionEvent = new RelatedCollectionEtagEvent($id);
        $etag = new Etag('someEtag');

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn((string) $etag);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagRelatedCollectionRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisRelatedCollectionEtagEventListener = new RedisRelatedCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisRelatedCollectionEtagEventListener->onRelatedCollectionEtagEvent($elementRelatedCollectionEvent);

        // assert event
        $this->assertTrue($elementRelatedCollectionEvent->isPropagationStopped());
        $this->assertSame('someEtag', (string) $elementRelatedCollectionEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for related collection in Redis.', [
            'centerId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:related:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
        ]);
        $data = $this->assertLogHappened($logger, 'debug', 'Found Etag for related collection in Redis.', false);
        $this->assertSame('02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1', $data['centerId']);
        $this->assertSame('etag:related:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1', $data['redisKey']);
        $this->assertSame('someEtag', (string) $data['etag']);
    }
}
