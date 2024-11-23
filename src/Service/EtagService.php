<?php

declare(strict_types=1);

namespace App\Service;

use App\EventSystem\Etag\Event\ChildrenCollectionEtagEvent;
use App\EventSystem\Etag\Event\ElementEtagEvent;
use App\EventSystem\Etag\Event\IndexCollectionEtagEvent;
use App\EventSystem\Etag\Event\ParentsCollectionEtagEvent;
use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
use App\Security\AuthProvider;
use App\Type\Etag;
use App\Type\EtagType;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

class EtagService
{
    private ?Etag $currentRequestEtag = null;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private AuthProvider $authProvider,
    ) {
    }

    public function setCurrentRequestEtagFromRequestAndEtagType(Request $request, EtagType $etagType): self
    {
        if (EtagType::INDEX_COLLECTION == $etagType) {
            $event = new IndexCollectionEtagEvent($this->authProvider->getUserId());
        } else {
            if (!$request->attributes->has('id')) {
                throw new Exception('Route should have attribute id.');
            }
            $requestId = Uuid::fromString($request->attributes->get('id'));
            switch ($etagType) {
                case EtagType::ELEMENT:
                    $event = new ElementEtagEvent($requestId);
                    break;
                case EtagType::CHILDREN_COLLECTION:
                    $event = new ChildrenCollectionEtagEvent($requestId);
                    break;
                case EtagType::PARENTS_COLLECTION:
                    $event = new ParentsCollectionEtagEvent($requestId);
                    break;
                case EtagType::RELATED_COLLECTION:
                    $event = new RelatedCollectionEtagEvent($requestId);
                    break;
            }
        }

        $this->eventDispatcher->dispatch($event);
        $this->currentRequestEtag = $event->getEtag();

        return $this;
    }

    public function getCurrentRequestEtag(): ?Etag
    {
        return $this->currentRequestEtag;
    }
}
