<?php

declare(strict_types=1);

namespace App\Service;

use App\EventSystem\Etag\Event\ChildrenCollectionEtagEvent;
use App\EventSystem\Etag\Event\ElementEtagEvent;
use App\EventSystem\Etag\Event\FileEtagEvent;
use App\EventSystem\Etag\Event\IndexCollectionEtagEvent;
use App\EventSystem\Etag\Event\ParentsCollectionEtagEvent;
use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
use App\Factory\Exception\Client404NotFoundExceptionFactory;
use App\Security\AccessChecker;
use App\Security\AuthProvider;
use App\Type\AccessType;
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
        private AccessChecker $accessChecker,
        private Client404NotFoundExceptionFactory $client404NotFoundExceptionFactory,
    ) {
    }

    public function setCurrentRequestEtagFromRequestAndEtagType(Request $request, EtagType $etagType): static
    {
        if (EtagType::INDEX_COLLECTION == $etagType) {
            $event = new IndexCollectionEtagEvent($this->authProvider->getUserId());
        } else {
            if (!$request->attributes->has('id')) {
                throw new Exception('Route should have attribute id.');
            }
            $requestId = Uuid::fromString($request->attributes->get('id'));
            // Conditional requests must not reveal whether an element exists or changed, so the user needs the same
            // access as the controller requires; answers exactly like the controller does without access.
            if (!$this->accessChecker->hasAccessToElement($this->authProvider->getUserId(), $requestId, $this->getRequiredAccessType($request, $etagType))) {
                throw $this->client404NotFoundExceptionFactory->createFromTemplate();
            }
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
                case EtagType::FILE:
                    $event = new FileEtagEvent($requestId);
                    break;
            }
        }

        $this->eventDispatcher->dispatch($event);
        $this->currentRequestEtag = $event->getEtag();

        return $this;
    }

    private function getRequiredAccessType(Request $request, EtagType $etagType): AccessType
    {
        if (in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return AccessType::READ;
        }
        if (EtagType::ELEMENT === $etagType && 'DELETE' === $request->getMethod()) {
            return AccessType::DELETE;
        }

        return AccessType::UPDATE;
    }

    public function getCurrentRequestEtag(): ?Etag
    {
        return $this->currentRequestEtag;
    }
}
