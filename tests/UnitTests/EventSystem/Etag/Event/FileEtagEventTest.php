<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\EventSystem\Etag\Event;

use App\EventSystem\Etag\Event\FileEtagEvent;
use App\Type\Etag;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
#[CoversClass(FileEtagEvent::class)]
class FileEtagEventTest extends TestCase
{
    public function testEventWithEtag(): void
    {
        $id = Uuid::fromString('02IiNr0G8viOVKkPBgDF3SqTNzjPCjqQzuDAf');
        $etag = new Etag('someEtag');
        $event = new FileEtagEvent($id);
        $event->setEtag($etag);

        $this->assertSame($etag, $event->getEtag());
        $this->assertSame($id, $event->getElementId());
    }

    public function testEventWithNull(): void
    {
        $id = Uuid::fromString('02IiNr0G8viOVKkPBgDF3SqTNzjPCjqQzuDAf');
        $event = new FileEtagEvent($id);
        $event->setEtag(null);

        $this->assertNull($event->getEtag());
        $this->assertSame($id, $event->getElementId());
    }
}
