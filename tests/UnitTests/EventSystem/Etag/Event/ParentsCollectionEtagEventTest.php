<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Etag\Event;

use App\EventSystem\Etag\Event\ParentsCollectionEtagEvent;
use App\Type\Etag;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(ParentsCollectionEtagEvent::class)]
class ParentsCollectionEtagEventTest extends TestCase
{
    public function testEventWithEtag(): void
    {
        $id = Uuid::fromString('02IiNr0G8viOVKkPBgDF3SqTNzjPCjqQzuDAf');
        $etag = new Etag('someEtag');
        $event = new ParentsCollectionEtagEvent($id);
        $event->setEtag($etag);

        $this->assertSame($etag, $event->getEtag());
        $this->assertSame($id, $event->getChildId());
    }

    public function testEventWithNull(): void
    {
        $id = Uuid::fromString('02IiNr0G8viOVKkPBgDF3SqTNzjPCjqQzuDAf');
        $event = new ParentsCollectionEtagEvent($id);
        $event->setEtag(null);

        $this->assertNull($event->getEtag());
        $this->assertSame($id, $event->getChildId());
    }
}
