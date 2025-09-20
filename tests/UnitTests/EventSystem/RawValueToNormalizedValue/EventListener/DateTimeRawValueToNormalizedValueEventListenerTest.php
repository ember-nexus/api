<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\RawValueToNormalizedValue\EventListener;

use App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent;
use App\EventSystem\RawValueToNormalizedValue\EventListener\DateTimeRawValueToNormalizedValueEventListener;
use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(DateTimeRawValueToNormalizedValueEventListener::class)]
class DateTimeRawValueToNormalizedValueEventListenerTest extends TestCase
{
    public function testNonStringValuesAreIgnored(): void
    {
        $eventListener = new DateTimeRawValueToNormalizedValueEventListener();
        $event = new RawValueToNormalizedValueEvent(1234);
        $eventListener->onRawValueToNormalizedValueEvent($event);
        $this->assertFalse($event->isPropagationStopped());
        $this->expectExceptionMessage('Typed property App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent::$normalizedValue must not be accessed before initialization');
        $event->getNormalizedValue();
    }

    public function testShortStringValuesAreIgnored(): void
    {
        $eventListener = new DateTimeRawValueToNormalizedValueEventListener();
        $event = new RawValueToNormalizedValueEvent('short');
        $eventListener->onRawValueToNormalizedValueEvent($event);
        $this->assertFalse($event->isPropagationStopped());
        $this->expectExceptionMessage('Typed property App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent::$normalizedValue must not be accessed before initialization');
        $event->getNormalizedValue();
    }

    public function testLongStringValuesAreIgnored(): void
    {
        $eventListener = new DateTimeRawValueToNormalizedValueEventListener();
        $event = new RawValueToNormalizedValueEvent('long-----x---------x---------x');
        $eventListener->onRawValueToNormalizedValueEvent($event);
        $this->assertFalse($event->isPropagationStopped());
        $this->expectExceptionMessage('Typed property App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent::$normalizedValue must not be accessed before initialization');
        $event->getNormalizedValue();
    }

    public function testNonDateStringValuesAreIgnored(): void
    {
        $eventListener = new DateTimeRawValueToNormalizedValueEventListener();
        $event = new RawValueToNormalizedValueEvent('2005-08-15Z15:52:01+00:00');
        $eventListener->onRawValueToNormalizedValueEvent($event);
        $this->assertFalse($event->isPropagationStopped());
        $this->expectExceptionMessage('Typed property App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent::$normalizedValue must not be accessed before initialization');
        $event->getNormalizedValue();
    }

    public function testDateStringValuesAreNormalized(): void
    {
        $eventListener = new DateTimeRawValueToNormalizedValueEventListener();
        $event = new RawValueToNormalizedValueEvent('2005-08-15T15:52:01+00:00');
        $eventListener->onRawValueToNormalizedValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $normalizedValue = $event->getNormalizedValue();
        $this->assertInstanceOf(DateTimeInterface::class, $normalizedValue);
        /**
         * @var DateTimeInterface $normalizedValue
         */
        $this->assertSame('2005-08-15T15:52:01+00:00', $normalizedValue->format(DateTime::ATOM));
    }

    public function testShortDateStringValuesAreNormalized(): void
    {
        $eventListener = new DateTimeRawValueToNormalizedValueEventListener();
        $event = new RawValueToNormalizedValueEvent('1-08-15T15:52:01+00:00');
        $eventListener->onRawValueToNormalizedValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $normalizedValue = $event->getNormalizedValue();
        $this->assertInstanceOf(DateTimeInterface::class, $normalizedValue);
        /**
         * @var DateTimeInterface $normalizedValue
         */
        $this->assertSame('0001-08-15T15:52:01+00:00', $normalizedValue->format(DateTime::ATOM));
    }
}
