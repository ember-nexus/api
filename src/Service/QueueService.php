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
        $jsonMessage = \Safe\json_encode($eventData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $message = new AMQPMessage($jsonMessage);
        $channel->basic_publish($message, '', $queue);
        $channel->close();
    }

    /**
     * Drains all messages currently waiting in the given queue, calling $handler for each decoded message.
     * Messages are only acknowledged once $handler returns without throwing, so a failure leaves the message
     * queued for the next run. Returns the number of successfully processed messages.
     */
    public function consumeQueue(RabbitMQQueueType $queueType, callable $handler): int
    {
        $channel = $this->AMQPStreamConnection->channel();
        $queue = $queueType->value;
        $channel->queue_declare($queue, false, false, false, false);

        $processedMessages = 0;
        while (null !== ($message = $channel->basic_get($queue))) {
            $eventData = \Safe\json_decode($message->getBody(), true);
            $handler($eventData);
            $message->ack();
            ++$processedMessages;
        }

        $channel->close();

        return $processedMessages;
    }
}
