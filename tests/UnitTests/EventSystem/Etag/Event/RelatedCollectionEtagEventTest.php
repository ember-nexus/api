<?php

namespace App\tests\UnitTests\EventSystem\Etag\Event;

use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class RelatedCollectionEtagEventTest extends TestCase
{
    public function testEventWithEtag(): void
    {
        $uuid = Uuid::fromString('87877759-2856-4b4a-80f6-df53296d12a4');
        $event = new RelatedCollectionEtagEvent($uuid);
        $event->setEtag('someEtag');

        $this->assertSame('someEtag', $event->getEtag());
        $this->assertSame($uuid, $event->getCenterUuid());
    }

    public function testEventWithNull(): void
    {
        $uuid = Uuid::fromString('87877759-2856-4b4a-80f6-df53296d12a4');
        $event = new RelatedCollectionEtagEvent($uuid);
        $event->setEtag(null);

        $this->assertNull($event->getEtag());
        $this->assertSame($uuid, $event->getCenterUuid());
    }
}
