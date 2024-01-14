<?php

namespace App\Service;

use App\EventSystem\Etag\Event\ChildrenCollectionEtagEvent;
use App\EventSystem\Etag\Event\ElementEtagEvent;
use App\EventSystem\Etag\Event\IndexCollectionEtagEvent;
use App\EventSystem\Etag\Event\ParentsCollectionEtagEvent;
use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ramsey\Uuid\UuidInterface;

class EtagService
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function getChildrenCollectionEtag(UuidInterface $parentUuid): ?string
    {
        $childrenCollectionEtagEvent = new ChildrenCollectionEtagEvent($parentUuid);
        $this->eventDispatcher->dispatch($childrenCollectionEtagEvent);

        return $childrenCollectionEtagEvent->getEtag();
    }

    public function getParentsCollectionEtag(UuidInterface $childUuid): ?string
    {
        $parentsCollectionEtagEvent = new ParentsCollectionEtagEvent($childUuid);
        $this->eventDispatcher->dispatch($parentsCollectionEtagEvent);

        return $parentsCollectionEtagEvent->getEtag();
    }

    public function getRelatedCollectionEtag(UuidInterface $centerUuid): ?string
    {
        $relatedCollectionEtagEvent = new RelatedCollectionEtagEvent($centerUuid);
        $this->eventDispatcher->dispatch($relatedCollectionEtagEvent);

        return $relatedCollectionEtagEvent->getEtag();
    }

    public function getIndexCollectionEtag(UuidInterface $userUuid): ?string
    {
        $indexCollectionEtagEvent = new IndexCollectionEtagEvent($userUuid);
        $this->eventDispatcher->dispatch($indexCollectionEtagEvent);

        return $indexCollectionEtagEvent->getEtag();
    }

    public function getElementEtag(UuidInterface $elementUuid): ?string
    {
        $elementEtagEvent = new ElementEtagEvent($elementUuid);
        $this->eventDispatcher->dispatch($elementEtagEvent);

        return $elementEtagEvent->getEtag();
    }
}
