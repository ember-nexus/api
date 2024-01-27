<?php

namespace App\tests\UnitTests\Service;

use App\EventSystem\Etag\Event\ChildrenCollectionEtagEvent;
use App\EventSystem\Etag\Event\ElementEtagEvent;
use App\EventSystem\Etag\Event\IndexCollectionEtagEvent;
use App\EventSystem\Etag\Event\ParentsCollectionEtagEvent;
use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
use App\Service\EtagService;
use App\Type\Etag;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ramsey\Uuid\Uuid;

class EtagServiceTest extends TestCase
{
    use ProphecyTrait;

    public function testGetChildrenCollectionEtag(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::type(ChildrenCollectionEtagEvent::class))->shouldBeCalledOnce()->will(function ($args) {
            /**
             * @var $event ChildrenCollectionEtagEvent
             */
            $event = $args[0];
            $event->setEtag(new Etag('syntheticEtag'));

            return $event;
        });

        $etagService = new EtagService($eventDispatcher->reveal());

        $etag = $etagService->getChildrenCollectionEtag(Uuid::fromString('85f27fba-0152-4087-a88a-cb7c601d1f37'));
        $this->assertSame('syntheticEtag', (string) $etag);
    }

    public function testGetParentsCollectionEtag(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::type(ParentsCollectionEtagEvent::class))->shouldBeCalledOnce()->will(function ($args) {
            /**
             * @var $event ParentsCollectionEtagEvent
             */
            $event = $args[0];
            $event->setEtag(new Etag('syntheticEtag'));

            return $event;
        });

        $etagService = new EtagService($eventDispatcher->reveal());

        $etag = $etagService->getParentsCollectionEtag(Uuid::fromString('85f27fba-0152-4087-a88a-cb7c601d1f37'));
        $this->assertSame('syntheticEtag', (string) $etag);
    }

    public function testGetRelatedCollectionEtag(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::type(RelatedCollectionEtagEvent::class))->shouldBeCalledOnce()->will(function ($args) {
            /**
             * @var $event RelatedCollectionEtagEvent
             */
            $event = $args[0];
            $event->setEtag(new Etag('syntheticEtag'));

            return $event;
        });

        $etagService = new EtagService($eventDispatcher->reveal());

        $etag = $etagService->getRelatedCollectionEtag(Uuid::fromString('85f27fba-0152-4087-a88a-cb7c601d1f37'));
        $this->assertSame('syntheticEtag', (string) $etag);
    }

    public function testGetIndexCollectionEtag(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::type(IndexCollectionEtagEvent::class))->shouldBeCalledOnce()->will(function ($args) {
            /**
             * @var $event IndexCollectionEtagEvent
             */
            $event = $args[0];
            $event->setEtag(new Etag('syntheticEtag'));

            return $event;
        });

        $etagService = new EtagService($eventDispatcher->reveal());

        $etag = $etagService->getIndexCollectionEtag(Uuid::fromString('85f27fba-0152-4087-a88a-cb7c601d1f37'));
        $this->assertSame('syntheticEtag', (string) $etag);
    }

    public function testGetElementEtag(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::type(ElementEtagEvent::class))->shouldBeCalledOnce()->will(function ($args) {
            /**
             * @var $event ElementEtagEvent
             */
            $event = $args[0];
            $event->setEtag(new Etag('syntheticEtag'));

            return $event;
        });

        $etagService = new EtagService($eventDispatcher->reveal());

        $etag = $etagService->getElementEtag(Uuid::fromString('85f27fba-0152-4087-a88a-cb7c601d1f37'));
        $this->assertSame('syntheticEtag', (string) $etag);
    }
}
