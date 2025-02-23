<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Etag\Event;

use App\EventSystem\Etag\Event\ElementEtagEvent;
use App\Type\Etag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

#[Small]
#[CoversClass(ElementEtagEvent::class)]
class ElementEtagEventTest extends TestCase
{
    public function testEventWithEtag(): void
    {
        $id = Uuid::fromString('87877759-2856-4b4a-80f6-df53296d12a4');
        $etag = new Etag('someEtag');
        $event = new ElementEtagEvent($id);
        $event->setEtag($etag);

        $this->assertSame($etag, $event->getEtag());
        $this->assertSame($id, $event->getElementId());
    }

    public function testEventWithNull(): void
    {
        $id = Uuid::fromString('87877759-2856-4b4a-80f6-df53296d12a4');
        $event = new ElementEtagEvent($id);
        $event->setEtag(null);

        $this->assertNull($event->getEtag());
        $this->assertSame($id, $event->getElementId());
    }
}
