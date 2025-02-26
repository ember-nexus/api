<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\IndexCollectionEtagEvent;
use App\EventSystem\Etag\EventListener\RedisIndexCollectionEtagEventListener;
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
#[CoversClass(RedisIndexCollectionEtagEventListener::class)]
class RedisIndexCollectionEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;
    use AssertLoggerTrait;

    public function testRedisIndexCollectionEtagEventListenerWithElementNotInRedis(): void
    {
        // setup variables
        $id = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_INDEX_COLLECTION, $id->toString());
        $elementIndexCollectionEvent = new IndexCollectionEtagEvent($id);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagIndexCollectionRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
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
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for index collection in Redis.', [
            'userId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:index:977245a7-a584-44bd-8992-1bfd80251a41',
        ]);
        $this->assertLogHappened($logger, 'debug', 'Unable to find Etag for index collection in Redis.', [
            'userId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:index:977245a7-a584-44bd-8992-1bfd80251a41',
        ]);
    }

    public function testRedisIndexCollectionEtagEventListenerWithElementInRedisWithNullValue(): void
    {
        // setup variables
        $id = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_INDEX_COLLECTION, $id->toString());
        $elementIndexCollectionEvent = new IndexCollectionEtagEvent($id);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn(RedisValueType::NULL->value);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagIndexCollectionRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
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
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for index collection in Redis.', [
            'userId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:index:977245a7-a584-44bd-8992-1bfd80251a41',
        ]);
        $this->assertLogHappened($logger, 'debug', 'Found Etag for index collection in Redis.', [
            'userId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:index:977245a7-a584-44bd-8992-1bfd80251a41',
            'etag' => null,
        ]);
    }

    public function testRedisIndexCollectionEtagEventListenerWithElementInRedisWithValue(): void
    {
        // setup variables
        $id = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_INDEX_COLLECTION, $id->toString());
        $elementIndexCollectionEvent = new IndexCollectionEtagEvent($id);
        $etag = new Etag('someEtag');

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn((string) $etag);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagIndexCollectionRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
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
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for index collection in Redis.', [
            'userId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:index:977245a7-a584-44bd-8992-1bfd80251a41',
        ]);
        $data = $this->assertLogHappened($logger, 'debug', 'Found Etag for index collection in Redis.', false);
        $this->assertSame('977245a7-a584-44bd-8992-1bfd80251a41', $data['userId']);
        $this->assertSame('etag:index:977245a7-a584-44bd-8992-1bfd80251a41', $data['redisKey']);
        $this->assertSame('someEtag', (string) $data['etag']);
    }
}
