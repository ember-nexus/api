<?php

namespace App\tests\UnitTests\EventSystem\NormalizedValueToRawValue\EventListener;

use App\EventSystem\NormalizedValueToRawValue\Event\NormalizedValueToRawValueEvent;
use App\EventSystem\NormalizedValueToRawValue\EventListener\DateTimeNormalizedValueToRawValueEventListener;
use DateTime;
use PHPUnit\Framework\TestCase;

class DateTimeNormalizedValueToRawValueEventListenerTest extends TestCase
{
    public function testNonDatetimeValuesAreIgnored(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }
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
         * @var $rawValue string
         */
        $this->assertSame('2005-08-15T15:52:01+00:00', $rawValue);
    }
}
