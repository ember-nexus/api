<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\EventSystem\RawValueToNormalizedValue\Event\RawValueToNormalizedValueEvent;
use App\Factory\Exception\Client400MissingPropertyExceptionFactory;
use App\Type\NodeElement;
use App\Type\RabbitMQQueueType;
use App\Type\RelationElement;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\EventDispatcher\EventDispatcherInterface;
use Ramsey\Uuid\Uuid;

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
