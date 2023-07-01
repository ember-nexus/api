<?php

namespace App\EventListener;

use App\Contract\RelationElementInterface;
use App\Event\ElementPostCreateEvent;
use App\Event\ElementPostDeleteEvent;
use App\Event\ElementPostMergeEvent;
use App\Type\RabbitMQQueueType;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

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
        if (!$type) {
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
        if (!is_string($jsonMessage)) {
            throw new \Exception('Internal server exception.');
        }
        $message = new AMQPMessage($jsonMessage);
        $channel->basic_publish($message, '', $queue);
        $channel->close();
    }
}
