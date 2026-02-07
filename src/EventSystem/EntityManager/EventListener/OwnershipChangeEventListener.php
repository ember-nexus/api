<?php

declare(strict_types=1);

namespace App\EventSystem\EntityManager\EventListener;

use App\Contract\RelationElementInterface;
use App\EventSystem\EntityManager\Event\ElementPostCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\Service\QueueService;
use App\Type\RabbitMQQueueType;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

use function Safe\json_encode;

class OwnershipChangeEventListener
{
    /**
     * @var string[]
     */
    private array $relationshipTypesWhichCanTriggerOwnershipChange = [
        'OWNS',
        'CREATED',
        'HAS_READ_ACCESS',
        'HAS_CREATE_ACCESS',
        'HAS_UPDATE_ACCESS',
        'HAS_DELETE_ACCESS',
        'HAS_SEARCH_ACCESS',
    ];

    public function __construct(
        private QueueService $queueService
    ) {
    }

    #[AsEventListener]
    public function onElementPostCreate(ElementPostCreateEvent $event): void
    {
        $this->handleEvent($event);
    }

    #[AsEventListener]
    public function onElementPostMerge(ElementPostMergeEvent $event): void
    {
        $this->handleEvent($event);
    }

    #[AsEventListener]
    public function onElementPostDelete(ElementPostDeleteEvent $event): void
    {
        $this->handleEvent($event);
    }

    private function handleEvent(ElementPostCreateEvent|ElementPostMergeEvent|ElementPostDeleteEvent $event): void
    {
        $element = $event->getElement();
        if (!($element instanceof RelationElementInterface)) {
            return;
        }
        $type = $element->getType();
        if (null === $type) {
            return;
        }
        if (array_key_exists($type, $this->relationshipTypesWhichCanTriggerOwnershipChange)) {
            $this->handleOwnershipChange($element);
        }
    }

    public function handleOwnershipChange(RelationElementInterface $element): void
    {
        $elementId = $element->getId();
        if (!$elementId) {
            return;
        }
        $this->queueService->publishEvent(
            RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE,
            [
                'element' => $elementId->toString(),
            ]
        );
    }
}
