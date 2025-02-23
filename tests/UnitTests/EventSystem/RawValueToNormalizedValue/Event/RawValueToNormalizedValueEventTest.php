<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\RawValueToNormalizedValue\Event;

use App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(RawValueToNormalizedValueEvent::class)]
class RawValueToNormalizedValueEventTest extends TestCase
{
    public function testNormalizedValueIsNotInitializedOnCreation(): void
    {
        $event = new RawValueToNormalizedValueEvent('raw value');
        $this->expectExceptionMessage('Typed property App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent::$normalizedValue must not be accessed before initialization');
        $event->getNormalizedValue();
    }

    public function testRawValueIsCorrectlySet(): void
    {
        $event = new RawValueToNormalizedValueEvent('raw value');
        $this->assertSame('raw value', $event->getRawValue());
    }

    public function testNormalizedValueIsCorrectlySet(): void
    {
        $event = new RawValueToNormalizedValueEvent('raw value');
        $event->setNormalizedValue('normalized value');
        $this->assertSame('normalized value', $event->getNormalizedValue());
    }
}
