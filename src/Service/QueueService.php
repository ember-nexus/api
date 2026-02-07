<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\RabbitMQQueueType;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class QueueService
{
    public function __construct(
        private AMQPStreamConnection $AMQPStreamConnection,
    ) {
    }

    public function publishEvent(RabbitMQQueueType $queueType, mixed $eventData): void
    {
        $channel = $this->AMQPStreamConnection->channel();
        $queue = $queueType->value;
        $channel->queue_declare($queue, false, false, false, false);
        $jsonMessage = json_encode($eventData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $message = new AMQPMessage($jsonMessage);
        $channel->basic_publish($message, '', $queue);
        $channel->close();
    }
}
