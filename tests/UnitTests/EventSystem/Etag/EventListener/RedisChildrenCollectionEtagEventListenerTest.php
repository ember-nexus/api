<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ChildrenCollectionEtagEvent;
use App\EventSystem\Etag\EventListener\RedisChildrenCollectionEtagEventListener;
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
#[CoversClass(RedisChildrenCollectionEtagEventListener::class)]
class RedisChildrenCollectionEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;
    use AssertLoggerTrait;

    public function testRedisChildrenCollectionEtagEventListenerWithElementNotInRedis(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_CHILDREN_COLLECTION, $id->toString());
        $elementChildrenCollectionEvent = new ChildrenCollectionEtagEvent($id);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagChildrenCollectionRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisChildrenCollectionEtagEventListener = new RedisChildrenCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisChildrenCollectionEtagEventListener->onChildrenCollectionEtagEvent($elementChildrenCollectionEvent);

        // assert event
        $this->assertFalse($elementChildrenCollectionEvent->isPropagationStopped());
        $this->assertNull($elementChildrenCollectionEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for children collection in Redis.', [
            'parentId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:children:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
        ]);
        $this->assertLogHappened($logger, 'debug', 'Unable to find Etag for children collection in Redis.', [
            'parentId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:children:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
        ]);
    }

    public function testRedisChildrenCollectionEtagEventListenerWithElementInRedisWithNullValue(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_CHILDREN_COLLECTION, $id->toString());
        $elementChildrenCollectionEvent = new ChildrenCollectionEtagEvent($id);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn(RedisValueType::NULL->value);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagChildrenCollectionRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisChildrenCollectionEtagEventListener = new RedisChildrenCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisChildrenCollectionEtagEventListener->onChildrenCollectionEtagEvent($elementChildrenCollectionEvent);

        // assert event
        $this->assertTrue($elementChildrenCollectionEvent->isPropagationStopped());
        $this->assertNull($elementChildrenCollectionEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for children collection in Redis.', [
            'parentId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:children:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
        ]);
        $this->assertLogHappened($logger, 'debug', 'Found Etag for children collection in Redis.', [
            'parentId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:children:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'etag' => null,
        ]);
    }

    public function testRedisChildrenCollectionEtagEventListenerWithElementInRedisWithValue(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_CHILDREN_COLLECTION, $id->toString());
        $elementChildrenCollectionEvent = new ChildrenCollectionEtagEvent($id);
        $etag = new Etag('someEtag');

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn((string) $etag);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagChildrenCollectionRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisChildrenCollectionEtagEventListener = new RedisChildrenCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisChildrenCollectionEtagEventListener->onChildrenCollectionEtagEvent($elementChildrenCollectionEvent);

        // assert event
        $this->assertTrue($elementChildrenCollectionEvent->isPropagationStopped());
        $this->assertSame('someEtag', (string) $elementChildrenCollectionEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for children collection in Redis.', [
            'parentId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:children:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
        ]);
        $data = $this->assertLogHappened($logger, 'debug', 'Found Etag for children collection in Redis.', false);
        $this->assertSame('02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1', $data['parentId']);
        $this->assertSame('etag:children:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1', $data['redisKey']);
        $this->assertSame('someEtag', (string) $data['etag']);
    }
}
