<?php

declare(strict_types=1);

namespace App\tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ElementEtagEvent;
use App\EventSystem\Etag\EventListener\LiveElementEtagEventListener;
use App\Factory\Type\RedisKeyFactory;
use App\Service\EtagCalculatorService;
use App\Type\Etag;
use App\Type\RedisKey;
use App\Type\RedisPrefixType;
use Beste\Psr\Log\TestLogger;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

class LiveElementEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;

    public function testLiveElementEtagEventListener(): void
    {
        // setup variables
        $uuid = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_ELEMENT, $uuid->toString());
        $elementEtagEvent = new ElementEtagEvent($uuid);
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
        $redisKeyFactory->getEtagElementRedisKey(Argument::is($uuid))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $etagCalculatorService = $this->prophesize(EtagCalculatorService::class);
        $etagCalculatorService->calculateElementEtag(Argument::is($uuid))->shouldBeCalledOnce()->willReturn($etag);

        $logger = TestLogger::create();

        // setup event listener
        $redisElementEtagEventListener = new LiveElementEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $etagCalculatorService->reveal(),
            $logger
        );

        // run event listener
        $redisElementEtagEventListener->onElementEtagEvent($elementEtagEvent);

        // assert event
        $this->assertTrue($elementEtagEvent->isPropagationStopped());
        $this->assertSame('someEtag', (string) $elementEtagEvent->getEtag());

        // assert logs
        $this->assertTrue($logger->records->includeMessagesContaining('Trying to persist Etag for element in Redis.'));
    }
}
