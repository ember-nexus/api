<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ElementEtagEvent;
use App\EventSystem\Etag\EventListener\RedisElementEtagEventListener;
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
#[CoversClass(RedisElementEtagEventListener::class)]
class RedisElementEtagEventListenerTest extends TestCase
{
    use ProphecyTrait;
    use AssertLoggerTrait;

    public function testRedisElementEtagEventListenerWithElementNotInRedis(): void
    {
        // setup variables
        $id = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_ELEMENT, $id->toString());
        $elementEtagEvent = new ElementEtagEvent($id);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn(null);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagElementRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisElementEtagEventListener = new RedisElementEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisElementEtagEventListener->onElementEtagEvent($elementEtagEvent);

        // assert event
        $this->assertFalse($elementEtagEvent->isPropagationStopped());
        $this->assertNull($elementEtagEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for element in Redis.', [
            'elementId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:element:977245a7-a584-44bd-8992-1bfd80251a41',
        ]);
        $this->assertLogHappened($logger, 'debug', 'Unable to find Etag for element in Redis.', [
            'elementId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:element:977245a7-a584-44bd-8992-1bfd80251a41',
        ]);
    }

    public function testRedisElementEtagEventListenerWithElementInRedisWithNullValue(): void
    {
        // setup variables
        $id = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_ELEMENT, $id->toString());
        $elementEtagEvent = new ElementEtagEvent($id);

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn(RedisValueType::NULL->value);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagElementRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisElementEtagEventListener = new RedisElementEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisElementEtagEventListener->onElementEtagEvent($elementEtagEvent);

        // assert event
        $this->assertTrue($elementEtagEvent->isPropagationStopped());
        $this->assertNull($elementEtagEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for element in Redis.', [
            'elementId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:element:977245a7-a584-44bd-8992-1bfd80251a41',
        ]);
        $this->assertLogHappened($logger, 'debug', 'Found Etag for element in Redis.', [
            'elementId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:element:977245a7-a584-44bd-8992-1bfd80251a41',
            'etag' => null,
        ]);
    }

    public function testRedisElementEtagEventListenerWithElementInRedisWithValue(): void
    {
        // setup variables
        $id = Uuid::fromString('977245a7-a584-44bd-8992-1bfd80251a41');
        $redisKey = new RedisKey(RedisPrefixType::ETAG_ELEMENT, $id->toString());
        $elementEtagEvent = new ElementEtagEvent($id);
        $etag = new Etag('someEtag');

        // setup event listener dependencies
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->get(Argument::is((string) $redisKey))->shouldBeCalledOnce()->willReturn((string) $etag);

        $redisKeyFactory = $this->prophesize(RedisKeyFactory::class);
        $redisKeyFactory->getEtagElementRedisKey(Argument::is($id))->shouldBeCalledOnce()->willReturn(
            $redisKey
        );

        $logger = TestLogger::create();

        // setup event listener
        $redisElementEtagEventListener = new RedisElementEtagEventListener(
            $redisClient->reveal(),
            $redisKeyFactory->reveal(),
            $logger
        );

        // run event listener
        $redisElementEtagEventListener->onElementEtagEvent($elementEtagEvent);

        // assert event
        $this->assertTrue($elementEtagEvent->isPropagationStopped());
        $this->assertSame('someEtag', (string) $elementEtagEvent->getEtag());

        // assert logs
        $this->assertLogHappened($logger, 'debug', 'Trying to find Etag for element in Redis.', [
            'elementId' => '977245a7-a584-44bd-8992-1bfd80251a41',
            'redisKey' => 'etag:element:977245a7-a584-44bd-8992-1bfd80251a41',
        ]);
        $data = $this->assertLogHappened($logger, 'debug', 'Found Etag for element in Redis.', false);
        $this->assertSame('977245a7-a584-44bd-8992-1bfd80251a41', $data['elementId']);
        $this->assertSame('etag:element:977245a7-a584-44bd-8992-1bfd80251a41', $data['redisKey']);
        $this->assertSame('someEtag', (string) $data['etag']);
    }
}
