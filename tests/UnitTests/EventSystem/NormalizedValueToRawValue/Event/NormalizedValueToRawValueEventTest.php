<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\NormalizedValueToRawValue\Event;

use App\EventSystem\NormalizedValueToRawValue\Event\NormalizedValueToRawValueEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(NormalizedValueToRawValueEvent::class)]
class NormalizedValueToRawValueEventTest extends TestCase
{
    public function testRawValueIsNotInitializedOnCreation(): void
    {
        $event = new NormalizedValueToRawValueEvent('normalized value');
        $this->expectExceptionMessage('Typed property App\EventSystem\NormalizedValueToRawValue\Event\NormalizedValueToRawValueEvent::$rawValue must not be accessed before initialization');
        $event->getRawValue();
    }

    public function testNormalizedValueIsCorrectlySet(): void
    {
        $event = new NormalizedValueToRawValueEvent('normalized value');
        $this->assertSame('normalized value', $event->getNormalizedValue());
    }

    public function testRawValueIsCorrectlySet(): void
    {
        $event = new NormalizedValueToRawValueEvent('normalized value');
        $event->setRawValue('raw value');
        $this->assertSame('raw value', $event->getRawValue());
    }
}
