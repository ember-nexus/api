<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ElementEtagEvent;
use App\EventSystem\Etag\EventListener\LiveElementEtagEventListener;
use App\Factory\Type\RedisKeyFactory;
use App\Service\EtagCalculatorService;
use App\Tests\UnitTests\AssertLoggerTrait;
use App\Type\Etag;
use App\Type\RedisKey;
use App\Type\RedisPrefixType;
use App\Type\Uuid;
use Beste\Psr\Log\TestLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(LiveElementEtagEventListener::class)]
class LiveElementEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;
    use AssertLoggerTrait;

    public function testLiveElementEtagEventListener(): void
    {
        // setup variables
        $id = Uuid::fromString('02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_ELEMENT, $id->toString());
        $elementEtagEvent = new ElementEtagEvent($id);
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
        $redisKeyFactory->getEtagElementRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $etagCalculatorService = $this->prophesize(EtagCalculatorService::class);
        $etagCalculatorService->calculateElementEtag(Argument::is($id))->shouldBeCalledOnce()->willReturn($etag);

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
        $this->assertLogHappened($logger, 'debug', 'Trying to persist Etag for element in Redis.', [
            'elementId' => '02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'redisKey' => 'etag:element:02IiNr0G8viOVKkWwoslFGBjEGp2K6GFczcU1',
            'etag' => $etag,
        ]);
    }
}
