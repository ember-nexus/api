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
use Beste\Psr\Log\TestLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(RedisChildrenCollectionEtagEventListener::class)]
class RedisChildrenCollectionEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;
    use AssertLoggerTrait;

    public function testRedisChildrenCollectionEtagEventListenerWithElementNotInRedis(): void
    {
        // setup variables
        $id = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
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
            'parentId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:children:977245a7-a584-44bd-8992-1bfd80251a41',
        ]);
        $this->assertLogHappened($logger, 'debug', 'Unable to find Etag for children collection in Redis.', [
            'parentId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:children:977245a7-a584-44bd-8992-1bfd80251a41',
        ]);
    }

    public function testRedisChildrenCollectionEtagEventListenerWithElementInRedisWithNullValue(): void
    {
        // setup variables
        $id = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
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
            'parentId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:children:977245a7-a584-44bd-8992-1bfd80251a41',
        ]);
        $this->assertLogHappened($logger, 'debug', 'Found Etag for children collection in Redis.', [
            'parentId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:children:977245a7-a584-44bd-8992-1bfd80251a41',
            'etag' => null,
        ]);
    }

    public function testRedisChildrenCollectionEtagEventListenerWithElementInRedisWithValue(): void
    {
        // setup variables
        $id = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
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
            'parentId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:children:977245a7-a584-44bd-8992-1bfd80251a41',
        ]);
        $data = $this->assertLogHappened($logger, 'debug', 'Found Etag for children collection in Redis.', false);
        $this->assertSame('977245a7-a584-44bd-8992-1bfd80251a41', $data['parentId']);
        $this->assertSame('etag:children:977245a7-a584-44bd-8992-1bfd80251a41', $data['redisKey']);
        $this->assertSame('someEtag', (string) $data['etag']);
    }
}
