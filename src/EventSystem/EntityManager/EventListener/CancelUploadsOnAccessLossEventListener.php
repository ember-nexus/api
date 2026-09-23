<?php

declare(strict_types=1);

namespace App\EventSystem\EntityManager\EventListener;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\Service\UploadCancellationService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

/**
 * Cancels uploads whose owner lost access to the upload's target. Changes of access-relevant elements only mark the
 * uploads for review; the review itself runs before the response is sent, as nested `flush()` calls of the element
 * manager are not supported.
 */
class CancelUploadsOnAccessLossEventListener
{
    /**
     * @var string[]
     */
    private const array ACCESS_RELATION_TYPES = ['OWNS', 'IS_IN_GROUP', 'HAS_UPDATE_ACCESS'];

    /**
     * @var string[]
     */
    private const array ACCESS_NODE_LABELS = ['User', 'Group'];

    private bool $reviewPending = false;

    public function __construct(
        private UploadCancellationService $uploadCancellationService,
    ) {
    }

    #[AsEventListener]
    public function onElementPostMerge(ElementPostMergeEvent $event): void
    {
        // only relations can lose access by being changed, nodes can not
        $element = $event->getElement();
        if ($element instanceof RelationElementInterface) {
            $this->markForReviewIfAccessRelevant($element);
        }
    }

    #[AsEventListener]
    public function onElementPostDelete(ElementPostDeleteEvent $event): void
    {
        $this->markForReviewIfAccessRelevant($event->getElement());
    }

    #[AsEventListener('kernel.response')]
    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->reviewPending) {
            return;
        }
        $this->reviewPending = false;
        $this->uploadCancellationService->cancelUploadsWithoutAccess();
    }

    private function markForReviewIfAccessRelevant(NodeElementInterface|RelationElementInterface $element): void
    {
        if ($element instanceof RelationElementInterface) {
            $this->reviewPending = $this->reviewPending || in_array($element->getType(), self::ACCESS_RELATION_TYPES, true);

            return;
        }
        // deleting a user or group detaches all of its relations without further events
        $this->reviewPending = $this->reviewPending || in_array($element->getLabel(), self::ACCESS_NODE_LABELS, true);
    }
}
