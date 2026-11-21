<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ChildrenCollectionEtagEvent;
use App\EventSystem\Etag\EventListener\LiveChildrenCollectionEtagEventListener;
use App\Factory\Type\RedisKeyFactory;
use App\Service\EtagCalculatorService;
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
#[CoversClass(LiveChildrenCollectionEtagEventListener::class)]
class LiveChildrenCollectionEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;
    use AssertLoggerTrait;

    public function testLiveChildrenCollectionEtagEventListenerWithFewEnoughChildren(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_CHILDREN_COLLECTION, $id->toString());
        $elementEtagEvent = new ChildrenCollectionEtagEvent($id);
        $etag = new Etag('someEtag');

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->set(
            Argument::is((string) $redisKey),
            Argument::is($etag),
            Argument::is('EX'),
            Argument::is(3600)
        )->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagChildrenCollectionRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $etagCalculatorService = $this->prophesize(EtagCalculatorService::class);
        $etagCalculatorService->calculateChildrenCollectionEtag(Argument::is($id))->shouldBeCalledOnce()->willReturn($etag);

        $logger = TestLogger::create();

        // setup event listener
        $redisChildrenCollectionEtagEventListener = new LiveChildrenCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $etagCalculatorService->reveal(),
            $logger
        );

        // run event listener
        $redisChildrenCollectionEtagEventListener->onChildrenCollectionEtagEvent($elementEtagEvent);

        // assert event
        $this->assertTrue($elementEtagEvent->isPropagationStopped());
        $this->assertSame('someEtag', (string) $elementEtagEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to persist Etag for children collection in Redis.', [
            'parentId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:children:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'etag' => $etag,
        ]);
    }

    public function testLiveChildrenCollectionEtagEventListenerWithTooManyChildren(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_CHILDREN_COLLECTION, $id->toString());
        $elementEtagEvent = new ChildrenCollectionEtagEvent($id);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->set(
            Argument::is((string) $redisKey),
            Argument::is(RedisValueType::NULL->value),
            Argument::is('EX'),
            Argument::is(3600)
        )->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagChildrenCollectionRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $etagCalculatorService = $this->prophesize(EtagCalculatorService::class);
        $etagCalculatorService->calculateChildrenCollectionEtag(Argument::is($id))->shouldBeCalledOnce()->willReturn(null);

        $logger = TestLogger::create();

        // setup event listener
        $redisChildrenCollectionEtagEventListener = new LiveChildrenCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $etagCalculatorService->reveal(),
            $logger
        );

        // run event listener
        $redisChildrenCollectionEtagEventListener->onChildrenCollectionEtagEvent($elementEtagEvent);

        // assert event
        $this->assertTrue($elementEtagEvent->isPropagationStopped());
        $this->assertNull($elementEtagEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to persist Etag for children collection in Redis.', [
            'parentId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:children:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'etag' => null,
        ]);
    }
}
