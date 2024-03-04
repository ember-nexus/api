<?php

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
        private AuthProvider $authProvider
    ) {
    }

    public function setCurrentRequestEtagFromRequestAndEtagType(Request $request, EtagType $etagType): self
    {
        $event = null;
        if (EtagType::INDEX_COLLECTION == $etagType) {
            $event = new IndexCollectionEtagEvent($this->authProvider->getUserUuid());
        } else {
            if (!$request->attributes->has('uuid')) {
                throw new Exception('Route should have attribute uuid.');
            }
            $requestUuid = Uuid::fromString($request->attributes->get('uuid'));
            switch ($etagType) {
                case EtagType::ELEMENT:
                    $event = new ElementEtagEvent($requestUuid);
                    break;
                case EtagType::CHILDREN_COLLECTION:
                    $event = new ChildrenCollectionEtagEvent($requestUuid);
                    break;
                case EtagType::PARENTS_COLLECTION:
                    $event = new ParentsCollectionEtagEvent($requestUuid);
                    break;
                case EtagType::RELATED_COLLECTION:
                    $event = new RelatedCollectionEtagEvent($requestUuid);
                    break;
                default:
                    throw new Exception('Reached unreachable statement');
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
