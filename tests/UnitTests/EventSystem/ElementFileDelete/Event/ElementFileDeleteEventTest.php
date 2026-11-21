<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementFileDelete\Event;

use App\EventSystem\ElementFileDelete\Event\ElementFileDeleteEvent;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ElementFileDeleteEvent::class)]
class ElementFileDeleteEventTest extends TestCase
{
    public function testElementFileDeleteEvent(): void
    {
        $id = Uuid::fromString('02IiNr0G8viOVKkvCjvI3PCgHMd8eudlJYPns');
        $event = new ElementFileDeleteEvent($id);

        $this->assertSame($id, $event->getElementId());
    }
}
