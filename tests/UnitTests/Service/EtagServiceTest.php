<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\EventSystem\Etag\Event\ChildrenCollectionEtagEvent;
use App\EventSystem\Etag\Event\ElementEtagEvent;
use App\EventSystem\Etag\Event\FileEtagEvent;
use App\EventSystem\Etag\Event\IndexCollectionEtagEvent;
use App\EventSystem\Etag\Event\ParentsCollectionEtagEvent;
use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
use App\Exception\Client404NotFoundException;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Service\EtagService;
use App\Type\AccessType;
use App\Type\Etag;
use App\Type\EtagType;
use App\Type\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

#[Small]
#[CoversClass(EtagService::class)]
class EtagServiceTest extends TestCase
{
    use ProphecyTrait;

    private function createAccessCheckerWithAccess(bool $hasAccess, ?AccessType $expectedAccessType = null): AccessChecker
    {
        $accessChecker = $this->prophesize(AccessChecker::class);
        $accessChecker->hasAccessToElement(Argument::any(), Argument::any(), $expectedAccessType ?? Argument::any())->willReturn($hasAccess);

        return $accessChecker->reveal();
    }

    private function createNotFoundFactory(): Client404NotFoundExceptionFactory
    {
        $factory = $this->prophesize(Client404NotFoundExceptionFactory::class);
        $factory->createFromTemplate()->willReturn(new Client404NotFoundException('type'));

        return $factory->reveal();
    }

    private function createAuthProviderWithUser(): AuthProvider
    {
        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->getUserId()->willReturn(Uuid::fromString('02IiNr0G8viOVKjTVopQ38JnV8PxJrftZpRV7'));

        return $authProvider->reveal();
    }

    public function testMissingAccessThrowsNotFoundAndDoesNotCalculateEtag(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcher->dispatch(Argument::any())->shouldNotBeCalled();

        $etagService = new EtagService(
            $eventDispatcher->reveal(),
            $this->createAuthProviderWithUser(),
            $this->createAccessCheckerWithAccess(false),
            $this->createNotFoundFactory()
        );

        $this->expectException(Client404NotFoundException::class);
        $etagService->setCurrentRequestEtagFromRequestAndEtagType(
            new Request(attributes: ['id' => '02IiNr0G8viOVKjbsoLbAzNdbleW5NVJZSwLP']),
            EtagType::ELEMENT
        );
    }

    public function testRequiredAccessTypeDependsOnMethodAndEtagType(): void
    {
        $cases = [
            ['GET', EtagType::ELEMENT, AccessType::READ],
            ['HEAD', EtagType::FILE, AccessType::READ],
            ['PATCH', EtagType::ELEMENT, AccessType::UPDATE],
            ['DELETE', EtagType::ELEMENT, AccessType::DELETE],
            ['DELETE', EtagType::FILE, AccessType::UPDATE],
            ['POST', EtagType::FILE, AccessType::UPDATE],
        ];
        foreach ($cases as [$method, $etagType, $accessType]) {
            $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
            $eventDispatcher->dispatch(Argument::any())->will(fn ($args) => $args[0]);

            $accessChecker = $this->prophesize(AccessChecker::class);
            $accessChecker->hasAccessToElement(Argument::any(), Argument::any(), $accessType)->willReturn(true)->shouldBeCalledOnce();

            $etagService = new EtagService(
                $eventDispatcher->reveal(),
                $this->createAuthProviderWithUser(),
                $accessChecker->reveal(),
                $this->createNotFoundFactory()
            );
            $etagService->setCurrentRequestEtagFromRequestAndEtagType(
                new Request(attributes: ['id' => '02IiNr0G8viOVKjbsoLbAzNdbleW5NVJZSwLP'], server: ['REQUEST_METHOD' => $method]),
                $etagType
            );
        }
        $this->addToAssertionCount(1);
    }

    public function testSetCurrentRequestEtagFromRequestAndEtagTypeWithEtagTypeElement(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $self = $this;
        $eventDispatcher->dispatch(Argument::type(ElementEtagEvent::class))->will(function ($args) use ($self) {
            $event = $args[0];
            /**
             * @var ElementEtagEvent $event
             */
            $self->assertSame('02IiNr0G8viOVKjbsoLbAzNdbleW5NVJZSwLP', $event->getElementId()->toString());
            $self->assertInstanceOf(ElementEtagEvent::class, $event);
            $event->setEtag(new Etag('someEtag'));
        })->shouldBeCalledTimes(1);

        $request = new Request(attributes: ['id' => '02IiNr0G8viOVKjbsoLbAzNdbleW5NVJZSwLP']);

        $etagService = new EtagService(
            $eventDispatcher->reveal(),
            $this->createAuthProviderWithUser(),
            $this->createAccessCheckerWithAccess(true),
            $this->createNotFoundFactory()
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
            $self->assertSame('02IiNr0G8viOVKjuArSoLxWuKY6LksuKcCKEP', $event->getParentId()->toString());
            $self->assertInstanceOf(ChildrenCollectionEtagEvent::class, $event);
            $event->setEtag(new Etag('someEtag'));
        })->shouldBeCalledTimes(1);

        $request = new Request(attributes: ['id' => '02IiNr0G8viOVKjuArSoLxWuKY6LksuKcCKEP']);

        $etagService = new EtagService(
            $eventDispatcher->reveal(),
            $this->createAuthProviderWithUser(),
            $this->createAccessCheckerWithAccess(true),
            $this->createNotFoundFactory()
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
            $self->assertSame('02IiNr0G8viOVKkA06oibQxRGVLfCEG5Ff1H2', $event->getChildId()->toString());
            $self->assertInstanceOf(ParentsCollectionEtagEvent::class, $event);
            $event->setEtag(new Etag('someEtag'));
        })->shouldBeCalledTimes(1);

        $request = new Request(attributes: ['id' => '02IiNr0G8viOVKkA06oibQxRGVLfCEG5Ff1H2']);

        $etagService = new EtagService(
            $eventDispatcher->reveal(),
            $this->createAuthProviderWithUser(),
            $this->createAccessCheckerWithAccess(true),
            $this->createNotFoundFactory()
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
            $self->assertSame('02IiNr0G8viOVKkhuD9OTeR2AF6U5cDbkxoaB', $event->getCenterId()->toString());
            $self->assertInstanceOf(RelatedCollectionEtagEvent::class, $event);
            $event->setEtag(new Etag('someEtag'));
        })->shouldBeCalledTimes(1);

        $request = new Request(attributes: ['id' => '02IiNr0G8viOVKkhuD9OTeR2AF6U5cDbkxoaB']);

        $etagService = new EtagService(
            $eventDispatcher->reveal(),
            $this->createAuthProviderWithUser(),
            $this->createAccessCheckerWithAccess(true),
            $this->createNotFoundFactory()
        );

        $returnedEtag = $etagService->setCurrentRequestEtagFromRequestAndEtagType($request, EtagType::RELATED_COLLECTION);
        $this->assertSame('someEtag', $returnedEtag->getCurrentRequestEtag()->getEtag());
    }

    public function testSetCurrentRequestEtagFromRequestAndEtagTypeWithEtagTypeFile(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);
        $self = $this;
        $eventDispatcher->dispatch(Argument::type(FileEtagEvent::class))->will(function ($args) use ($self) {
            $event = $args[0];
            /**
             * @var FileEtagEvent $event
             */
            $self->assertSame('02IiNr0G8viOVKl9gw3VOEjF028rKn5PwGfdw', $event->getElementId()->toString());
            $self->assertInstanceOf(FileEtagEvent::class, $event);
            $event->setEtag(new Etag('someEtag'));
        })->shouldBeCalledTimes(1);

        $request = new Request(attributes: ['id' => '02IiNr0G8viOVKl9gw3VOEjF028rKn5PwGfdw']);

        $etagService = new EtagService(
            $eventDispatcher->reveal(),
            $this->createAuthProviderWithUser(),
            $this->createAccessCheckerWithAccess(true),
            $this->createNotFoundFactory()
        );

        $returnedEtag = $etagService->setCurrentRequestEtagFromRequestAndEtagType($request, EtagType::FILE);
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
            $self->assertSame('02IiNr0G8viOVKjqWP6FkrAQPIIA3k8D7AmRg', $event->getUserId()->toString());
            $self->assertInstanceOf(IndexCollectionEtagEvent::class, $event);
            $event->setEtag(new Etag('someEtag'));
        })->shouldBeCalledTimes(1);
        $authProvider = $this->prophesize(AuthProvider::class);
        $authProvider->getUserId()->shouldBeCalledOnce()->willReturn(Uuid::fromString('02IiNr0G8viOVKjqWP6FkrAQPIIA3k8D7AmRg'));

        $request = new Request(attributes: ['id' => '02IiNr0G8viOVKjqWP6FkrAQPIIA3k8D7AmRg']);

        $etagService = new EtagService(
            $eventDispatcher->reveal(),
            $authProvider->reveal(),
            $this->createAccessCheckerWithAccess(true),
            $this->createNotFoundFactory()
        );

        $returnedEtag = $etagService->setCurrentRequestEtagFromRequestAndEtagType($request, EtagType::INDEX_COLLECTION);
        $this->assertSame('someEtag', $returnedEtag->getCurrentRequestEtag()->getEtag());
    }

    public function testSetCurrentRequestEtagFromRequestAndEtagTypeWithEtagTypeElementAndRequestWithoutAttribute(): void
    {
        $eventDispatcher = $this->prophesize(EventDispatcherInterface::class);

        $request = new Request();

        $etagService = new EtagService(
            $eventDispatcher->reveal(),
            $this->createAuthProviderWithUser(),
            $this->createAccessCheckerWithAccess(true),
            $this->createNotFoundFactory()
        );

        $this->expectExceptionMessage('Route should have attribute id.');

        $etagService->setCurrentRequestEtagFromRequestAndEtagType($request, EtagType::ELEMENT);
    }
}
