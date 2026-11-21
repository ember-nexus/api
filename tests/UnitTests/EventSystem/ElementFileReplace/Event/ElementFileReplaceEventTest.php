<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementFileReplace\Event;

use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ElementFileReplaceEvent::class)]
class ElementFileReplaceEventTest extends TestCase
{
    public function testElementFileReplaceEvent(): void
    {
        $id = Uuid::fromString('02IiNr0G8viOVKkzFnQyCyT6Fq4s7mRM0KObd');
        $event = new ElementFileReplaceEvent($id);

        $this->assertSame($id, $event->getElementId());
    }
}
