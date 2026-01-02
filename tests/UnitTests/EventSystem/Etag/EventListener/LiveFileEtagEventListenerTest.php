<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\FileEtagEvent;
use App\EventSystem\Etag\EventListener\LiveFileEtagEventListener;
use App\Factory\Type\RedisKeyFactory;
use App\Service\EtagCalculatorService;
use App\Tests\UnitTests\AssertLoggerTrait;
use App\Type\Etag;
use App\Type\RedisKey;
use App\Type\RedisPrefixType;
use Beste\Psr\Log\TestLogger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(LiveFileEtagEventListener::class)]
class LiveFileEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;
    use AssertLoggerTrait;

    public function testLiveFileEtagEventListener(): void
    {
        // setup variables
        $id = Uuid::fromString('5fe57bf8-799d-4a19-9d0e-8566035521e1');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_FILE, $id->toString());
        $fileEtagEvent = new FileEtagEvent($id);
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
        $redisKeyFactory->getEtagFileRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $etagCalculatorService = $this->prophesize(EtagCalculatorService::class);
        $etagCalculatorService->calculateFileEtag(Argument::is($id))->shouldBeCalledOnce()->willReturn($etag);

        $logger = TestLogger::create();

        // setup event listener
        $redisFileEtagEventListener = new LiveFileEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $etagCalculatorService->reveal(),
            $logger
        );

        // run event listener
        $redisFileEtagEventListener->onFileEtagEvent($fileEtagEvent);

        // assert event
        $this->assertTrue($fileEtagEvent->isPropagationStopped());
        $this->assertSame('someEtag', (string) $fileEtagEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to persist Etag for file in Redis.', [
            'elementId' => '5fe57bf8-799d-4a19-9d0e-8566035521e1',
            'redisKey' => 'etag:file:5fe57bf8-799d-4a19-9d0e-8566035521e1',
            'etag' => $etag,
        ]);
    }
}
