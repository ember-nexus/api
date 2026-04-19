<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\ElementFileReplace\Event;

use App\EventSystem\ElementFileReplace\Event\ElementFileReplaceEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(ElementFileReplaceEvent::class)]
class ElementFileReplaceEventTest extends TestCase
{
    public function testElementFileReplaceEvent(): void
    {
        $id = Uuid::fromString('d19eb7bb-72eb-4547-a76f-0dfc727a62ef');
        $event = new ElementFileReplaceEvent($id);

        $this->assertSame($id, $event->getElementId());
    }
}
