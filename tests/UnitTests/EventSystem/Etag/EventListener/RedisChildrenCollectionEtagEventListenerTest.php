<?php

namespace App\tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ChildrenCollectionEtagEvent;
use App\EventSystem\Etag\EventListener\RedisChildrenCollectionEtagEventListener;
use App\Factory\Type\RedisKeyFactory;
use App\Type\RedisKey;
use App\Type\RedisPrefixType;
use App\Type\RedisValueType;
use Beste\Psr\Log\TestLogger;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

class RedisChildrenCollectionEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testRedisChildrenCollectionEtagEventListenerWithElementNotInRedis(): void
    {
        // setup variables
        $uuid = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_CHILDREN_COLLECTION, $uuid->toString());
        $elementChildrenCollectionEvent = new ChildrenCollectionEtagEvent($uuid);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is($redisKey))->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagChildrenCollectionRedisKey(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(
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
        $this->assertTrue($logger->records->includeMessagesContaining('Trying to find Etag for children collection in Redis.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Unable to find Etag for children collection in Redis.'));
    }

    public function testRedisChildrenCollectionEtagEventListenerWithElementInRedisWithNullValue(): void
    {
        // setup variables
        $uuid = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_CHILDREN_COLLECTION, $uuid->toString());
        $elementChildrenCollectionEvent = new ChildrenCollectionEtagEvent($uuid);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is($redisKey))->shouldBeCalledOnce()->willReturn(RedisValueType::NULL->value);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagChildrenCollectionRedisKey(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(
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
        $this->assertTrue($logger->records->includeMessagesContaining('Trying to find Etag for children collection in Redis.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Found Etag for children collection in Redis.'));
    }

    public function testRedisChildrenCollectionEtagEventListenerWithElementInRedisWithValue(): void
    {
        // setup variables
        $uuid = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_CHILDREN_COLLECTION, $uuid->toString());
        $elementChildrenCollectionEvent = new ChildrenCollectionEtagEvent($uuid);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is($redisKey))->shouldBeCalledOnce()->willReturn('someEtag');

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagChildrenCollectionRedisKey(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(
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
        $this->assertSame('someEtag', $elementChildrenCollectionEvent->getEtag());

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Trying to find Etag for children collection in Redis.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Found Etag for children collection in Redis.'));
    }
}
