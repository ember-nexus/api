<?php

namespace App\tests\UnitTests\EventSystem\Etag\Event;

use App\EventSystem\Etag\Event\ParentsCollectionEtagEvent;
use App\Type\Etag;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class ParentsCollectionEtagEventTest extends TestCase
{
    public function testEventWithEtag(): void
    {
        $uuid = Uuid::fromString('87877759-2856-4b4a-80f6-df53296d12a4');
        $etag = new Etag('someEtag');
        $event = new ParentsCollectionEtagEvent($uuid);
        $event->setEtag($etag);

        $this->assertSame($etag, $event->getEtag());
        $this->assertSame($uuid, $event->getChildUuid());
    }

    public function testEventWithNull(): void
    {
        $uuid = Uuid::fromString('87877759-2856-4b4a-80f6-df53296d12a4');
        $event = new ParentsCollectionEtagEvent($uuid);
        $event->setEtag(null);

        $this->assertNull($event->getEtag());
        $this->assertSame($uuid, $event->getChildUuid());
    }
}
