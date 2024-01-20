<?php

namespace App\tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
use App\EventSystem\Etag\EventListener\LiveRelatedCollectionEtagEventListener;
use App\Factory\Type\RedisKeyFactory;
use App\Service\EtagCalculatorService;
use App\Type\RedisKey;
use App\Type\RedisPrefixType;
use App\Type\RedisValueType;
use Beste\Psr\Log\TestLogger;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

class LiveRelatedCollectionEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testLiveRelatedCollectionEtagEventListenerWithFewEnoughRelated(): void
    {
        // setup variables
        $uuid = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_RELATED_COLLECTION, $uuid->toString());
        $elementEtagEvent = new RelatedCollectionEtagEvent($uuid);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->set(
            Argument::is($redisKey),
            Argument::is('someEtag'),
            Argument::is('EX'),
            Argument::is(3600)
        )->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagRelatedCollectionRedisKey(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $etagCalculatorService = $this->prophesize(EtagCalculatorService::class);
        $etagCalculatorService->calculateRelatedCollectionEtag(Argument::is($uuid))->shouldBeCalledOnce()->willReturn('someEtag');

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
        $this->assertSame('someEtag', $elementEtagEvent->getEtag());

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Trying to persist Etag for related collection in Redis.'));
    }

    public function testLiveRelatedCollectionEtagEventListenerWithTooManyRelated(): void
    {
        // setup variables
        $uuid = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_RELATED_COLLECTION, $uuid->toString());
        $elementEtagEvent = new RelatedCollectionEtagEvent($uuid);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->set(
            Argument::is($redisKey),
            Argument::is(RedisValueType::NULL->value),
            Argument::is('EX'),
            Argument::is(3600)
        )->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagRelatedCollectionRedisKey(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $etagCalculatorService = $this->prophesize(EtagCalculatorService::class);
        $etagCalculatorService->calculateRelatedCollectionEtag(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(null);

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
        $this->assertTrue($logger->records->includeMessagesContaining('Trying to persist Etag for related collection in Redis.'));
    }
}
