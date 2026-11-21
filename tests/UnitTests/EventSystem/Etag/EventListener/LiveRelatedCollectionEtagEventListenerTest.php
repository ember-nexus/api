<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
use App\EventSystem\Etag\EventListener\LiveRelatedCollectionEtagEventListener;
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
#[CoversClass(LiveRelatedCollectionEtagEventListener::class)]
class LiveRelatedCollectionEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;
    use AssertLoggerTrait;

    public function testLiveRelatedCollectionEtagEventListenerWithFewEnoughRelated(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_RELATED_COLLECTION, $id->toString());
        $elementEtagEvent = new RelatedCollectionEtagEvent($id);
        $etag = new Etag('someEtag');

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->set(
            Argument::is((string) $redisKey),
            Argument::is((string) $etag),
            Argument::is('EX'),
            Argument::is(3600)
        )->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagRelatedCollectionRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $etagCalculatorService = $this->prophesize(EtagCalculatorService::class);
        $etagCalculatorService->calculateRelatedCollectionEtag(Argument::is($id))->shouldBeCalledOnce()->willReturn($etag);

        $logger = TestLogger::create();

        // setup event listener
        $redisRelatedCollectionEtagEventListener = new LiveRelatedCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $etagCalculatorService->reveal(),
            $logger
        );

        // run event listener
        $redisRelatedCollectionEtagEventListener->onRelatedCollectionEtagEvent($elementEtagEvent);

        // assert event
        $this->assertTrue($elementEtagEvent->isPropagationStopped());
        $this->assertSame('someEtag', (string) $elementEtagEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to persist Etag for related collection in Redis.', [
            'centerId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:related:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'etag' => $etag,
        ]);
    }

    public function testLiveRelatedCollectionEtagEventListenerWithTooManyRelated(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_RELATED_COLLECTION, $id->toString());
        $elementEtagEvent = new RelatedCollectionEtagEvent($id);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->set(
            Argument::is((string) $redisKey),
            Argument::is(RedisValueType::NULL->value),
            Argument::is('EX'),
            Argument::is(3600)
        )->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagRelatedCollectionRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $etagCalculatorService = $this->prophesize(EtagCalculatorService::class);
        $etagCalculatorService->calculateRelatedCollectionEtag(Argument::is($id))->shouldBeCalledOnce()->willReturn(null);

        $logger = TestLogger::create();

        // setup event listener
        $redisRelatedCollectionEtagEventListener = new LiveRelatedCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $etagCalculatorService->reveal(),
            $logger
        );

        // run event listener
        $redisRelatedCollectionEtagEventListener->onRelatedCollectionEtagEvent($elementEtagEvent);

        // assert event
        $this->assertTrue($elementEtagEvent->isPropagationStopped());
        $this->assertNull($elementEtagEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to persist Etag for related collection in Redis.', [
            'centerId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:related:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'etag' => null,
        ]);
    }
}
