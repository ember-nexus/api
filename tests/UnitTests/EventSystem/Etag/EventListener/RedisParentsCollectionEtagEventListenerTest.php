<?php

namespace App\tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ParentsCollectionEtagEvent;
use App\EventSystem\Etag\EventListener\RedisParentsCollectionEtagEventListener;
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

class RedisParentsCollectionEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testRedisParentsCollectionEtagEventListenerWithElementNotInRedis(): void
    {
        // setup variables
        $uuid = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_PARENTS_COLLECTION, $uuid->toString());
        $elementParentsCollectionEvent = new ParentsCollectionEtagEvent($uuid);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is($redisKey))->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagParentsCollectionRedisKey(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisParentsCollectionEtagEventListener = new RedisParentsCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisParentsCollectionEtagEventListener->onParentsCollectionEtagEvent($elementParentsCollectionEvent);

        // assert event
        $this->assertFalse($elementParentsCollectionEvent->isPropagationStopped());
        $this->assertNull($elementParentsCollectionEvent->getEtag());

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Trying to find Etag for parents collection in Redis.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Unable to find Etag for parents collection in Redis.'));
    }

    public function testRedisParentsCollectionEtagEventListenerWithElementInRedisWithNullValue(): void
    {
        // setup variables
        $uuid = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_PARENTS_COLLECTION, $uuid->toString());
        $elementParentsCollectionEvent = new ParentsCollectionEtagEvent($uuid);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is($redisKey))->shouldBeCalledOnce()->willReturn(RedisValueType::NULL->value);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagParentsCollectionRedisKey(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisParentsCollectionEtagEventListener = new RedisParentsCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisParentsCollectionEtagEventListener->onParentsCollectionEtagEvent($elementParentsCollectionEvent);

        // assert event
        $this->assertTrue($elementParentsCollectionEvent->isPropagationStopped());
        $this->assertNull($elementParentsCollectionEvent->getEtag());

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Trying to find Etag for parents collection in Redis.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Found Etag for parents collection in Redis.'));
    }

    public function testRedisParentsCollectionEtagEventListenerWithElementInRedisWithValue(): void
    {
        // setup variables
        $uuid = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_PARENTS_COLLECTION, $uuid->toString());
        $elementParentsCollectionEvent = new ParentsCollectionEtagEvent($uuid);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is($redisKey))->shouldBeCalledOnce()->willReturn('someEtag');

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagParentsCollectionRedisKey(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisParentsCollectionEtagEventListener = new RedisParentsCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisParentsCollectionEtagEventListener->onParentsCollectionEtagEvent($elementParentsCollectionEvent);

        // assert event
        $this->assertTrue($elementParentsCollectionEvent->isPropagationStopped());
        $this->assertSame('someEtag', $elementParentsCollectionEvent->getEtag());

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Trying to find Etag for parents collection in Redis.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Found Etag for parents collection in Redis.'));
    }
}
