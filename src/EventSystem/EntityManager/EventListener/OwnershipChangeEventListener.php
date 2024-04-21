<?php

declare(strict_types=1);

namespace App\EventSystem\EntityManager\EventListener;

use App\Contract\RelationElementInterface;
use App\EventSystem\EntityManager\Event\ElementPostCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\Type\RabbitMQQueueType;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

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
        private AMQPStreamConnection $AMQPStreamConnection
    ) {
    }

    public function onElementPostCreate(ElementPostCreateEvent $event): void
    {
        $this->handleEvent($event);
    }

    public function onElementPostMerge(ElementPostMergeEvent $event): void
    {
        $this->handleEvent($event);
    }

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
        $elementId = $element->getIdentifier();
        if (!$elementId) {
            return;
        }
        $channel = $this->AMQPStreamConnection->channel();
        $queue = RabbitMQQueueType::REBUILD_SEARCH_DOCUMENT_QUEUE->value;
        $channel->queue_declare($queue, false, false, false, false);
        $jsonMessage = json_encode([
            'element' => $elementId->toString(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $message = new AMQPMessage($jsonMessage);
        $channel->basic_publish($message, '', $queue);
        $channel->close();
    }
}
