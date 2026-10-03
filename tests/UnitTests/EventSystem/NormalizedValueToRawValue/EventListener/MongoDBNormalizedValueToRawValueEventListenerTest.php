<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\NormalizedValueToRawValue\EventListener;

use App\EventSystem\NormalizedValueToRawValue\Event\NormalizedValueToRawValueEvent;
use App\EventSystem\NormalizedValueToRawValue\EventListener\MongoDBNormalizedValueToRawValueEventListener;
use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use stdClass;

#[Small]
#[CoversClass(MongoDBNormalizedValueToRawValueEventListener::class)]
class MongoDBNormalizedValueToRawValueEventListenerTest extends TestCase
{
    public function testNonBsonValuesAreIgnored(): void
    {
        $eventListener = new MongoDBNormalizedValueToRawValueEventListener();
        foreach (['string', ['a' => 1], new stdClass()] as $value) {
            $event = new NormalizedValueToRawValueEvent($value);
            $eventListener->onNormalizedValueToRawValueEvent($event);
            $this->assertFalse($event->isPropagationStopped());
        }
    }

    public function testBsonArrayIsConvertedToArray(): void
    {
        $event = new NormalizedValueToRawValueEvent(new BSONArray([1, 2, 3]));
        (new MongoDBNormalizedValueToRawValueEventListener())->onNormalizedValueToRawValueEvent($event);

        $this->assertTrue($event->isPropagationStopped());
        $this->assertSame([1, 2, 3], $event->getRawValue());
    }

    public function testBsonDocumentIsConvertedToArray(): void
    {
        $event = new NormalizedValueToRawValueEvent(new BSONDocument(['a' => 1, 'b' => 'c']));
        (new MongoDBNormalizedValueToRawValueEventListener())->onNormalizedValueToRawValueEvent($event);

        $this->assertTrue($event->isPropagationStopped());
        $this->assertSame(['a' => 1, 'b' => 'c'], $event->getRawValue());
    }
}
