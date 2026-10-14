<?php

declare(strict_types=1);

namespace App\EventSystem\EntityManager\EventListener;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\Security\AuthProvider;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Group nodes carry access rights for everyone in them, so changing or removing one is more consequential than an
 * ordinary node write; logged on the dedicated, always-flushed `audit` channel instead of regular request logging
 * (see monolog.yaml).
 */
#[WithMonologChannel('audit')]
class GroupChangeAuditEventListener
{
    public function __construct(
        private AuthProvider $authProvider,
        private LoggerInterface $logger,
    ) {
    }

    #[AsEventListener]
    public function onElementPostMerge(ElementPostMergeEvent $event): void
    {
        $this->logIfGroup($event->getElement(), 'Group node was updated.');
    }

    #[AsEventListener]
    public function onElementPostDelete(ElementPostDeleteEvent $event): void
    {
        $this->logIfGroup($event->getElement(), 'Group node was deleted.');
    }

    private function logIfGroup(NodeElementInterface|RelationElementInterface $element, string $message): void
    {
        if (!($element instanceof NodeElementInterface)) {
            return;
        }
        if ('Group' !== $element->getLabel()) {
            return;
        }
        $elementId = $element->getId();
        if (null === $elementId) {
            return;
        }

        $this->logger->notice(
            $message,
            [
                'elementId' => $elementId->toString(),
                'client' => [
                    'user' => $this->authProvider->isAnonymous() ? 'anonymous' : $this->authProvider->getUserId()->toString(),
                    'token' => $this->authProvider->getTokenId()?->toString(),
                ],
            ]
        );
    }
}
