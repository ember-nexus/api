<?php

declare(strict_types=1);

namespace App\tests\UnitTests\EventSystem\Etag\Event;

use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
use App\Type\Etag;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

class RelatedCollectionEtagEventTest extends TestCase
{
    public function testEventWithEtag(): void
    {
        $id = Uuid::fromString('87877759-2856-4b4a-80f6-df53296d12a4');
        $etag = new Etag('someEtag');
        $event = new RelatedCollectionEtagEvent($id);
        $event->setEtag($etag);

        $this->assertSame($etag, $event->getEtag());
        $this->assertSame($id, $event->getCenterId());
    }

    public function testEventWithNull(): void
    {
        $id = Uuid::fromString('87877759-2856-4b4a-80f6-df53296d12a4');
        $event = new RelatedCollectionEtagEvent($id);
        $event->setEtag(null);

        $this->assertNull($event->getEtag());
        $this->assertSame($id, $event->getCenterId());
    }
}
