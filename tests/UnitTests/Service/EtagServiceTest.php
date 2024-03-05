<?php

namespace App\tests\UnitTests\Service;

use App\EventSystem\Etag\Event\ChildrenCollectionEtagEvent;
use App\EventSystem\Etag\Event\ElementEtagEvent;
use App\EventSystem\Etag\Event\IndexCollectionEtagEvent;
use App\EventSystem\Etag\Event\ParentsCollectionEtagEvent;
use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
use App\Security\AuthProvider;
use App\Service\EtagService;
use App\Type\Etag;
use App\Type\EtagType;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

class EtagServiceTest extends TestCase
{
    use ProphecyTrait;

    public function testSetCurrentRequestEtagFromRequestAndEtagTypeWithEtagTypeElement(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $self = $this;
        $eventDispatcher->dispatch(Argument::type(ElementEtagEvent::class))->will(function ($args) use ($self) {
            $event = $args[0];
            /**
             * @var ElementEtagEvent $event
             */
            $self->assertSame('224b322a-c2a1-4971-8b05-28af080d67f1', $event->getElementUuid()->toString());
            $event->setEtag(new Etag('someEtag'));
        })->shouldBeCalledTimes(1);
        $authProvider = $this->prophesize(AuthProvider::class);

        $request = new Request(attributes: ['uuid' => '224b322a-c2a1-4971-8b05-28af080d67f1']);

        $etagService = new EtagService(
            $eventDispatcher->reveal(),
            $authProvider->reveal()
        );

        $returnedEtag = $etagService->setCurrentRequestEtagFromRequestAndEtagType($request, EtagType::ELEMENT);
        $this->assertSame('someEtag', $returnedEtag->getCurrentRequestEtag()->getEtag());
    }

    public function testSetCurrentRequestEtagFromRequestAndEtagTypeWithEtagTypeChildrenCollection(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $self = $this;
        $eventDispatcher->dispatch(Argument::type(ChildrenCollectionEtagEvent::class))->will(function ($args) use ($self) {
            $event = $args[0];
            /**
             * @var ChildrenCollectionEtagEvent $event
             */
            $self->assertSame('47d86985-68e0-4747-8921-33f3a9090549', $event->getParentUuid()->toString());
            $event->setEtag(new Etag('someEtag'));
        })->shouldBeCalledTimes(1);
        $authProvider = $this->prophesize(AuthProvider::class);

        $request = new Request(attributes: ['uuid' => '47d86985-68e0-4747-8921-33f3a9090549']);

        $etagService = new EtagService(
            $eventDispatcher->reveal(),
            $authProvider->reveal()
        );

        $returnedEtag = $etagService->setCurrentRequestEtagFromRequestAndEtagType($request, EtagType::CHILDREN_COLLECTION);
        $this->assertSame('someEtag', $returnedEtag->getCurrentRequestEtag()->getEtag());
    }

    public function testSetCurrentRequestEtagFromRequestAndEtagTypeWithEtagTypeParentsCollection(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $self = $this;
        $eventDispatcher->dispatch(Argument::type(ParentsCollectionEtagEvent::class))->will(function ($args) use ($self) {
            $event = $args[0];
            /**
             * @var ParentsCollectionEtagEvent $event
             */
            $self->assertSame('685b5a01-f2d2-4764-9c69-3fd87c45d5b0', $event->getChildUuid()->toString());
            $event->setEtag(new Etag('someEtag'));
        })->shouldBeCalledTimes(1);
        $authProvider = $this->prophesize(AuthProvider::class);

        $request = new Request(attributes: ['uuid' => '685b5a01-f2d2-4764-9c69-3fd87c45d5b0']);

        $etagService = new EtagService(
            $eventDispatcher->reveal(),
            $authProvider->reveal()
        );

        $returnedEtag = $etagService->setCurrentRequestEtagFromRequestAndEtagType($request, EtagType::PARENTS_COLLECTION);
        $this->assertSame('someEtag', $returnedEtag->getCurrentRequestEtag()->getEtag());
    }

    public function testSetCurrentRequestEtagFromRequestAndEtagTypeWithEtagTypeRelatedCollection(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $self = $this;
        $eventDispatcher->dispatch(Argument::type(RelatedCollectionEtagEvent::class))->will(function ($args) use ($self) {
            $event = $args[0];
            /**
             * @var RelatedCollectionEtagEvent $event
             */
            $self->assertSame('adfd47a3-7d25-4bdb-b546-f5744382d488', $event->getCenterUuid()->toString());
            $event->setEtag(new Etag('someEtag'));
        })->shouldBeCalledTimes(1);
        $authProvider = $this->prophesize(AuthProvider::class);

        $request = new Request(attributes: ['uuid' => 'adfd47a3-7d25-4bdb-b546-f5744382d488']);

        $etagService = new EtagService(
            $eventDispatcher->reveal(),
            $authProvider->reveal()
        );

        $returnedEtag = $etagService->setCurrentRequestEtagFromRequestAndEtagType($request, EtagType::RELATED_COLLECTION);
        $this->assertSame('someEtag', $returnedEtag->getCurrentRequestEtag()->getEtag());
    }

    public function testSetCurrentRequestEtagFromRequestAndEtagTypeWithEtagTypeIndexCollection(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $self = $this;
        $eventDispatcher->dispatch(Argument::type(IndexCollectionEtagEvent::class))->will(function ($args) use ($self) {
            $event = $args[0];
            /**
             * @var IndexCollectionEtagEvent $event
             */
            $self->assertSame('405599eb-f72b-4505-9ad6-fabe458e9607', $event->getUserUuid()->toString());
            $event->setEtag(new Etag('someEtag'));
        })->shouldBeCalledTimes(1);
        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->getUserUuid()->shouldBeCalledOnce()->willReturn(Uuid::fromString('405599eb-f72b-4505-9ad6-fabe458e9607'));

        $request = new Request(attributes: ['uuid' => '405599eb-f72b-4505-9ad6-fabe458e9607']);

        $etagService = new EtagService(
            $eventDispatcher->reveal(),
            $authProvider->reveal()
        );

        $returnedEtag = $etagService->setCurrentRequestEtagFromRequestAndEtagType($request, EtagType::INDEX_COLLECTION);
        $this->assertSame('someEtag', $returnedEtag->getCurrentRequestEtag()->getEtag());
    }

    public function testSetCurrentRequestEtagFromRequestAndEtagTypeWithEtagTypeElementAndRequestWithoutAttribute(): void
    {
        if (array_key_exists('LEAK', $_ENV)) {
            $this->markTestSkipped();
        }
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $authProvider = $this->prophesize(AuthProvider::class);

        $request = new Request();

        $etagService = new EtagService(
            $eventDispatcher->reveal(),
            $authProvider->reveal()
        );

        $this->expectExceptionMessage('Route should have attribute uuid.');

        $etagService->setCurrentRequestEtagFromRequestAndEtagType($request, EtagType::ELEMENT);
    }
}
