<?php

declare(strict_types=1);

namespace App\tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\IndexCollectionEtagEvent;
use App\EventSystem\Etag\EventListener\RedisIndexCollectionEtagEventListener;
use App\Factory\Type\RedisKeyFactory;
use App\Type\Etag;
use App\Type\RedisKey;
use App\Type\RedisPrefixType;
use App\Type\RedisValueType;
use Beste\Psr\Log\TestLogger;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

class RedisIndexCollectionEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testRedisIndexCollectionEtagEventListenerWithElementNotInRedis(): void
    {
        // setup variables
        $uuid = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_INDEX_COLLECTION, $uuid->toString());
        $elementIndexCollectionEvent = new IndexCollectionEtagEvent($uuid);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagIndexCollectionRedisKey(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisIndexCollectionEtagEventListener = new RedisIndexCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisIndexCollectionEtagEventListener->onIndexCollectionEtagEvent($elementIndexCollectionEvent);

        // assert event
        $this->assertFalse($elementIndexCollectionEvent->isPropagationStopped());
        $this->assertNull($elementIndexCollectionEvent->getEtag());

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Trying to find Etag for index collection in Redis.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Unable to find Etag for index collection in Redis.'));
    }

    public function testRedisIndexCollectionEtagEventListenerWithElementInRedisWithNullValue(): void
    {
        // setup variables
        $uuid = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_INDEX_COLLECTION, $uuid->toString());
        $elementIndexCollectionEvent = new IndexCollectionEtagEvent($uuid);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn(RedisValueType::NULL->value);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagIndexCollectionRedisKey(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisIndexCollectionEtagEventListener = new RedisIndexCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisIndexCollectionEtagEventListener->onIndexCollectionEtagEvent($elementIndexCollectionEvent);

        // assert event
        $this->assertTrue($elementIndexCollectionEvent->isPropagationStopped());
        $this->assertNull($elementIndexCollectionEvent->getEtag());

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Trying to find Etag for index collection in Redis.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Found Etag for index collection in Redis.'));
    }

    public function testRedisIndexCollectionEtagEventListenerWithElementInRedisWithValue(): void
    {
        // setup variables
        $uuid = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_INDEX_COLLECTION, $uuid->toString());
        $elementIndexCollectionEvent = new IndexCollectionEtagEvent($uuid);
        $etag = new Etag('someEtag');

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn((string) $etag);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagIndexCollectionRedisKey(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisIndexCollectionEtagEventListener = new RedisIndexCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisIndexCollectionEtagEventListener->onIndexCollectionEtagEvent($elementIndexCollectionEvent);

        // assert event
        $this->assertTrue($elementIndexCollectionEvent->isPropagationStopped());
        $this->assertSame('someEtag', (string) $elementIndexCollectionEvent->getEtag());

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Trying to find Etag for index collection in Redis.'));
        $this->assertTrue($logger->records->includeMessagesContaining('Found Etag for index collection in Redis.'));
    }
}
