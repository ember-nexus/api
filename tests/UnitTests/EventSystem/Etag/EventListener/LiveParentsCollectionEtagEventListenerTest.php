<?php

namespace App\tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ParentsCollectionEtagEvent;
use App\EventSystem\Etag\EventListener\LiveParentsCollectionEtagEventListener;
use App\Factory\Type\RedisKeyFactory;
use App\Service\EtagCalculatorService;
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

class LiveParentsCollectionEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testLiveParentsCollectionEtagEventListenerWithFewEnoughParents(): void
    {
        // setup variables
        $uuid = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_PARENTS_COLLECTION, $uuid->toString());
        $elementEtagEvent = new ParentsCollectionEtagEvent($uuid);
        $etag = new Etag('someEtag');

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->set(
            Argument::is($redisKey),
            Argument::is($etag),
            Argument::is('EX'),
            Argument::is(3600)
        )->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagParentsCollectionRedisKey(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $etagCalculatorService = $this->prophesize(EtagCalculatorService::class);
        $etagCalculatorService->calculateParentsCollectionEtag(Argument::is($uuid))->shouldBeCalledOnce()->willReturn($etag);

        $logger = TestLogger::create();

        // setup event listener
        $redisParentsCollectionEtagEventListener = new LiveParentsCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $etagCalculatorService->reveal(),
            $logger
        );

        // run event listener
        $redisParentsCollectionEtagEventListener->onParentsCollectionEtagEvent($elementEtagEvent);

        // assert event
        $this->assertTrue($elementEtagEvent->isPropagationStopped());
        $this->assertSame('someEtag', (string) $elementEtagEvent->getEtag());

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Trying to persist Etag for parents collection in Redis.'));
    }

    public function testLiveParentsCollectionEtagEventListenerWithTooManyParents(): void
    {
        // setup variables
        $uuid = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_PARENTS_COLLECTION, $uuid->toString());
        $elementEtagEvent = new ParentsCollectionEtagEvent($uuid);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->set(
            Argument::is($redisKey),
            Argument::is(RedisValueType::NULL->value),
            Argument::is('EX'),
            Argument::is(3600)
        )->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagParentsCollectionRedisKey(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $etagCalculatorService = $this->prophesize(EtagCalculatorService::class);
        $etagCalculatorService->calculateParentsCollectionEtag(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(null);

        $logger = TestLogger::create();

        // setup event listener
        $redisParentsCollectionEtagEventListener = new LiveParentsCollectionEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $etagCalculatorService->reveal(),
            $logger
        );

        // run event listener
        $redisParentsCollectionEtagEventListener->onParentsCollectionEtagEvent($elementEtagEvent);

        // assert event
        $this->assertTrue($elementEtagEvent->isPropagationStopped());
        $this->assertNull($elementEtagEvent->getEtag());

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Trying to persist Etag for parents collection in Redis.'));
    }
}
