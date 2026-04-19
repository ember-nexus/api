<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementFileDelete\Event;

use App\EventSystem\ElementFileDelete\Event\ElementFileDeleteEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(ElementFileDeleteEvent::class)]
class ElementFileDeleteEventTest extends TestCase
{
    public function testElementFileDeleteEvent(): void
    {
        $id = Uuid::fromString('c9429d25-3ace-449c-b094-50aab28fc8a5');
        $event = new ElementFileDeleteEvent($id);

        $this->assertSame($id, $event->getElementId());
    }
}
