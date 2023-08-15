<?php

namespace App\Tests\UnitTests\EventSystem\NormalizedValueToRawValue\EventListener;

use App\EventSystem\NormalizedValueToRawValue\Event\NormalizedValueToRawValueEvent;
use App\EventSystem\NormalizedValueToRawValue\EventListener\GenericNormalizedValueToRawValueEventListener;
use PHPUnit\Framework\TestCase;

class GenericNormalizedValueToRawValueEventListenerTest extends TestCase
{
    public function testIntegerValuesAreDenormalized(): void
    {
        $eventListener = new GenericNormalizedValueToRawValueEventListener();
        $event = new NormalizedValueToRawValueEvent(1);
        $eventListener->onNormalizedValueToRawValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertIsInt($event->getNormalizedValue());
        $this->assertSame(1, $event->getNormalizedValue());
    }

    public function testFloatValuesAreDenormalized(): void
    {
        $eventListener = new GenericNormalizedValueToRawValueEventListener();
        $event = new NormalizedValueToRawValueEvent(1.234);
        $eventListener->onNormalizedValueToRawValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertIsFloat($event->getNormalizedValue());
        $this->assertGreaterThan(1.233, $event->getNormalizedValue());
        $this->assertLessThan(1.235, $event->getNormalizedValue());
    }

    public function testBoolValuesAreDenormalized(): void
    {
        $eventListener = new GenericNormalizedValueToRawValueEventListener();

        $trueEvent = new NormalizedValueToRawValueEvent(true);
        $eventListener->onNormalizedValueToRawValueEvent($trueEvent);
        $this->assertTrue($trueEvent->isPropagationStopped());
        $this->assertIsBool($trueEvent->getNormalizedValue());
        $this->assertTrue($trueEvent->getNormalizedValue());

        $falseEvent = new NormalizedValueToRawValueEvent(false);
        $eventListener->onNormalizedValueToRawValueEvent($falseEvent);
        $this->assertTrue($falseEvent->isPropagationStopped());
        $this->assertIsBool($falseEvent->getNormalizedValue());
        $this->assertFalse($falseEvent->getNormalizedValue());
    }

    public function testEmptyStringValuesAreDenormalized(): void
    {
        $eventListener = new GenericNormalizedValueToRawValueEventListener();
        $event = new NormalizedValueToRawValueEvent('');
        $eventListener->onNormalizedValueToRawValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertIsString($event->getNormalizedValue());
        $this->assertSame('', $event->getNormalizedValue());
    }

    public function testStringValuesAreDenormalized(): void
    {
        $eventListener = new GenericNormalizedValueToRawValueEventListener();
        $event = new NormalizedValueToRawValueEvent('Hello world :D');
        $eventListener->onNormalizedValueToRawValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertIsString($event->getNormalizedValue());
        $this->assertSame('Hello world :D', $event->getNormalizedValue());
    }

    public function testNullValuesAreDenormalized(): void
    {
        $eventListener = new GenericNormalizedValueToRawValueEventListener();
        $event = new NormalizedValueToRawValueEvent(null);
        $eventListener->onNormalizedValueToRawValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertNull($event->getNormalizedValue());
        $this->assertSame(null, $event->getNormalizedValue());
    }

    public function testArrayValuesAreDenormalized(): void
    {
        $eventListener = new GenericNormalizedValueToRawValueEventListener();
        $event = new NormalizedValueToRawValueEvent(['a', 'c', 'b']);
        $eventListener->onNormalizedValueToRawValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertIsArray($event->getNormalizedValue());
        $normalizedValue = $event->getNormalizedValue();
        /**
         * @var $normalizedValue array
         */
        $this->assertSame(3, count($normalizedValue));
        $this->assertSame('a', $normalizedValue[0]);
        $this->assertSame('c', $normalizedValue[1]);
        $this->assertSame('b', $normalizedValue[2]);
    }

    public function testAssociativeArrayValuesAreDenormalized(): void
    {
        $eventListener = new GenericNormalizedValueToRawValueEventListener();
        $event = new NormalizedValueToRawValueEvent(['A' => 'a', 'C' => 'c', 'B' => 'b']);
        $eventListener->onNormalizedValueToRawValueEvent($event);
        $this->assertTrue($event->isPropagationStopped());
        $this->assertIsArray($event->getNormalizedValue());
        $normalizedValue = $event->getNormalizedValue();
        /**
         * @var $normalizedValue array
         */
        $this->assertSame(3, count($normalizedValue));
        $this->assertSame('a', $normalizedValue['A']);
        $this->assertSame('b', $normalizedValue['B']);
        $this->assertSame('c', $normalizedValue['C']);
    }
}
