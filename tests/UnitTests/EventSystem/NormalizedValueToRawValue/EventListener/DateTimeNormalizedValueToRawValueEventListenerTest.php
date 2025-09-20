<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\NormalizedValueToRawValue\EventListener;

use App\EventSystem\NormalizedValueToRawValue\Event\NormalizedValueToRawValueEvent;
use App\EventSystem\NormalizedValueToRawValue\EventListener\DateTimeNormalizedValueToRawValueEventListener;
use DateTime;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(DateTimeNormalizedValueToRawValueEventListener::class)]
class DateTimeNormalizedValueToRawValueEventListenerTest extends TestCase
{
    public function testNonDatetimeValuesAreIgnored(): void
    {
        $eventListener = new DateTimeNormalizedValueToRawValueEventListener();
        $event = new NormalizedValueToRawValueEvent('not a datetime');
        $eventListener->onNormalizedValueToRawValueEvent($event);
        $this->assertFalse($event->isPropagationStopped());
        $this->expectExceptionMessage('Typed property App\EventSystem\NormalizedValueToRawValue\Event\NormalizedValueToRawValueEvent::$rawValue must not be accessed before initialization');
        $event->getRawValue();
    }

    public function testDatetimeValuesAreDenormalized(): void
    {
        $eventListener = new DateTimeNormalizedValueToRawValueEventListener();
        $event = new NormalizedValueToRawValueEvent(DateTime::createFromFormat(DateTime::ATOM, '2005-08-15T15:52:01+00:00'));
        $eventListener->onNormalizedValueToRawValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $rawValue = $event->getRawValue();
        $this->assertIsString($rawValue);
        /**
         * @var string $rawValue
         */
        $this->assertSame('2005-08-15T15:52:01+00:00', $rawValue);
    }
}
