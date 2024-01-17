<?php

namespace App\tests\UnitTests\EventSystem\Etag\Event;

use App\EventSystem\Etag\Event\ElementEtagEvent;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class ElementEtagEventTest extends TestCase
{
    public function testEventWithEtag(): void
    {
        $uuid = Uuid::fromString('87877759-2856-4b4a-80f6-df53296d12a4');
        $event = new ElementEtagEvent($uuid);
        $event->setEtag('someEtag');

        $this->assertSame('someEtag', $event->getEtag());
        $this->assertSame($uuid, $event->getElementUuid());
    }

    public function testEventWithNull(): void
    {
        $uuid = Uuid::fromString('87877759-2856-4b4a-80f6-df53296d12a4');
        $event = new ElementEtagEvent($uuid);
        $event->setEtag(null);

        $this->assertNull($event->getEtag());
        $this->assertSame($uuid, $event->getElementUuid());
    }
}
