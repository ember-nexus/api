<?php

namespace App\Service;

use App\Type\RabbitMQQueueType;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Ramsey\Uuid\UuidInterface;

class RecheckSearchAccessService
{
    public function __construct(
        private AMQPStreamConnection $AMQPStreamConnection
    ) {
    }

    public function markElementToBeCheckedInFuture(UuidInterface $elementUuid): void
    {
        $channel = $this->AMQPStreamConnection->channel();
        $queue = RabbitMQQueueType::REBUILD_SEARCH_DOCUMENT_QUEUE->value;
        $channel->queue_declare($queue, false, false, false, false);
        $jsonMessage = json_encode([
            'element' => $elementUuid->toString(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $message = new AMQPMessage($jsonMessage);
        $channel->basic_publish($message, '', $queue);
        $channel->close();
    }

    public function getUsersAndGroupsWithSearchAccessToElement(UuidInterface $elementUuid): void
    {
    }
}
