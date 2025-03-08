<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\RawValueToNormalizedValue\EventListener;

use App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent;
use App\EventSystem\RawValueToNormalizedValue\EventListener\GenericRawValueToNormalizedValueEventListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(GenericRawValueToNormalizedValueEventListener::class)]
class GenericRawValueToNormalizedValueEventListenerTest extends TestCase
{
    public function testIntegerValuesAreNormalized(): void
    {
        $eventListener = new GenericRawValueToNormalizedValueEventListener();
        $event = new RawValueToNormalizedValueEvent(1);
        $eventListener->onRawValueToNormalizedValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertIsInt($event->getNormalizedValue());
        $this->assertSame(1, $event->getNormalizedValue());
    }

    public function testFloatValuesAreNormalized(): void
    {
        $eventListener = new GenericRawValueToNormalizedValueEventListener();
        $event = new RawValueToNormalizedValueEvent(1.234);
        $eventListener->onRawValueToNormalizedValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertIsFloat($event->getNormalizedValue());
        $this->assertGreaterThan(1.233, $event->getNormalizedValue());
        $this->assertLessThan(1.235, $event->getNormalizedValue());
    }

    public function testBoolValuesAreNormalized(): void
    {
        $eventListener = new GenericRawValueToNormalizedValueEventListener();

        $trueEvent = new RawValueToNormalizedValueEvent(true);
        $eventListener->onRawValueToNormalizedValueEvent($trueEvent);
        $this->assertTrue($trueEvent->isPropagationStopped());
        $this->assertIsBool($trueEvent->getNormalizedValue());
        $this->assertTrue($trueEvent->getNormalizedValue());

        $falseEvent = new RawValueToNormalizedValueEvent(false);
        $eventListener->onRawValueToNormalizedValueEvent($falseEvent);
        $this->assertTrue($falseEvent->isPropagationStopped());
        $this->assertIsBool($falseEvent->getNormalizedValue());
        $this->assertFalse($falseEvent->getNormalizedValue());
    }

    public function testEmptyStringValuesAreNormalized(): void
    {
        $eventListener = new GenericRawValueToNormalizedValueEventListener();
        $event = new RawValueToNormalizedValueEvent('');
        $eventListener->onRawValueToNormalizedValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertIsString($event->getNormalizedValue());
        $this->assertSame('', $event->getNormalizedValue());
    }

    public function testStringValuesAreNormalized(): void
    {
        $eventListener = new GenericRawValueToNormalizedValueEventListener();
        $event = new RawValueToNormalizedValueEvent('Hello world :D');
        $eventListener->onRawValueToNormalizedValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertIsString($event->getNormalizedValue());
        $this->assertSame('Hello world :D', $event->getNormalizedValue());
    }

    public function testNullValuesAreNormalized(): void
    {
        $eventListener = new GenericRawValueToNormalizedValueEventListener();
        $event = new RawValueToNormalizedValueEvent(null);
        $eventListener->onRawValueToNormalizedValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertNull($event->getNormalizedValue());
        $this->assertSame(null, $event->getNormalizedValue());
    }

    public function testArrayValuesAreNormalized(): void
    {
        $eventListener = new GenericRawValueToNormalizedValueEventListener();
        $event = new RawValueToNormalizedValueEvent(['a', 'c', 'b']);
        $eventListener->onRawValueToNormalizedValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertIsArray($event->getNormalizedValue());
        $normalizedValue = $event->getNormalizedValue();
        /**
         * @var array $normalizedValue
         */
        $this->assertSame(3, count($normalizedValue));
        $this->assertSame('a', $normalizedValue[0]);
        $this->assertSame('c', $normalizedValue[1]);
        $this->assertSame('b', $normalizedValue[2]);
    }

    public function testAssociativeArrayValuesAreNormalized(): void
    {
        $eventListener = new GenericRawValueToNormalizedValueEventListener();
        $event = new RawValueToNormalizedValueEvent(['A' => 'a', 'C' => 'c', 'B' => 'b']);
        $eventListener->onRawValueToNormalizedValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertIsArray($event->getNormalizedValue());
        $normalizedValue = $event->getNormalizedValue();
        /**
         * @var array $normalizedValue
         */
        $this->assertSame(3, count($normalizedValue));
        $this->assertSame('a', $normalizedValue['A']);
        $this->assertSame('b', $normalizedValue['B']);
        $this->assertSame('c', $normalizedValue['C']);
    }
}
