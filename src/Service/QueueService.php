<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\RabbitMQQueueType;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;
use Throwable;

class QueueService
{
    public const int MAX_TRIES = 3;
    private const string TRY_COUNT_HEADER = 'x-try-count';

    /**
     * Queue types which are declared durable, with persistent messages and with a bounded length + dead-letter
     * target. Every other queue type keeps the historical non-durable, non-persistent, unbounded declaration, so
     * this list is opt-in per queue rather than a blanket change.
     *
     * @var RabbitMQQueueType[]
     */
    private const array DURABLE_QUEUE_TYPES = [
        RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE,
    ];

    private const int MAX_QUEUE_LENGTH = 100_000;

    public function __construct(
        private AMQPStreamConnection $AMQPStreamConnection,
        private LoggerInterface $logger,
    ) {
    }

    public function publishEvent(RabbitMQQueueType $queueType, mixed $eventData): void
    {
        $channel = $this->AMQPStreamConnection->channel();
        $queue = $queueType->value;
        $channel = $this->declareQueue($channel, $queueType);
        $jsonMessage = \Safe\json_encode($eventData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $properties = $this->isDurable($queueType) ? ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT] : [];
        $message = new AMQPMessage($jsonMessage, $properties);
        $channel->basic_publish($message, '', $queue);
        $channel->close();
    }

    /**
     * Drains all messages currently waiting in the given queue, calling $handler for each decoded message.
     * Messages are acknowledged once $handler returns without throwing. A message which fails (or can not be decoded) is
     * logged, republished at the end of the queue with an increased try counter (after the queue was drained, so it is
     * not consumed again in this run) and acknowledged; after MAX_TRIES failed tries it is dropped and this is logged.
     * Returns the number of successfully processed messages.
     */
    public function consumeQueue(RabbitMQQueueType $queueType, callable $handler): int
    {
        $channel = $this->AMQPStreamConnection->channel();
        $queue = $queueType->value;
        $channel = $this->declareQueue($channel, $queueType);

        $processedMessages = 0;
        /** @var array<array{body: string, tries: int}> $messagesToRequeue */
        $messagesToRequeue = [];
        while (null !== ($message = $channel->basic_get($queue))) {
            try {
                $eventData = \Safe\json_decode($message->getBody(), true);
                $handler($eventData);
                ++$processedMessages;
            } catch (Throwable $throwable) {
                $tries = $this->getTryCount($message) + 1;
                if ($tries >= self::MAX_TRIES) {
                    $this->logger->error(sprintf(
                        "Dropping message of queue '%s' after %d failed tries: %s. Message body: %s",
                        $queue,
                        $tries,
                        $throwable->getMessage(),
                        $message->getBody()
                    ));
                } else {
                    $this->logger->error(sprintf(
                        "Failed to process message of queue '%s' (try %d of %d), requeueing it: %s. Message body: %s",
                        $queue,
                        $tries,
                        self::MAX_TRIES,
                        $throwable->getMessage(),
                        $message->getBody()
                    ));
                    $messagesToRequeue[] = ['body' => $message->getBody(), 'tries' => $tries];
                }
            }
            $message->ack();
        }

        $isDurable = $this->isDurable($queueType);
        foreach ($messagesToRequeue as $messageToRequeue) {
            $properties = [
                'application_headers' => new AMQPTable([self::TRY_COUNT_HEADER => $messageToRequeue['tries']]),
            ];
            if ($isDurable) {
                $properties['delivery_mode'] = AMQPMessage::DELIVERY_MODE_PERSISTENT;
            }
            $channel->basic_publish(new AMQPMessage($messageToRequeue['body'], $properties), '', $queue);
        }

        $channel->close();

        return $processedMessages;
    }

    private function getTryCount(AMQPMessage $message): int
    {
        if (!$message->has('application_headers')) {
            return 0;
        }
        $headers = $message->get('application_headers');
        if (!($headers instanceof AMQPTable)) {
            return 0;
        }
        $tries = $headers->getNativeData()[self::TRY_COUNT_HEADER] ?? 0;

        return is_int($tries) ? $tries : 0;
    }

    private function isDurable(RabbitMQQueueType $queueType): bool
    {
        return in_array($queueType, self::DURABLE_QUEUE_TYPES, true);
    }

    /**
     * Declares the given queue, using durable/persistent/bounded-length + dead-letter arguments for the queue types
     * opted into that via {@see self::DURABLE_QUEUE_TYPES}, or the historical plain declaration otherwise.
     *
     * If a queue of the same name was already declared elsewhere with different arguments (e.g. left over from
     * before this queue type became durable), RabbitMQ refuses the redeclaration with a channel-level
     * `PRECONDITION_FAILED` (406) error, which also closes the channel. Since nothing has ever consumed this queue
     * for real yet (verified: no production consumer exists), it is safe to drop and recreate it with the new
     * arguments on a fresh channel rather than requiring a queue rename/migration.
     */
    private function declareQueue(AMQPChannel $channel, RabbitMQQueueType $queueType): AMQPChannel
    {
        $queue = $queueType->value;
        if (!$this->isDurable($queueType)) {
            $channel->queue_declare($queue, false, false, false, false);

            return $channel;
        }

        $deadLetterQueue = $queue.'.dead-letter';
        $arguments = new AMQPTable([
            'x-max-length' => self::MAX_QUEUE_LENGTH,
            'x-dead-letter-exchange' => '',
            'x-dead-letter-routing-key' => $deadLetterQueue,
        ]);

        try {
            $channel->queue_declare($deadLetterQueue, false, true, false, false, false, new AMQPTable(['x-max-length' => self::MAX_QUEUE_LENGTH]));
            $channel->queue_declare($queue, false, true, false, false, false, $arguments);

            return $channel;
        } catch (AMQPProtocolChannelException $exception) {
            if (406 !== $exception->amqp_reply_code) {
                throw $exception;
            }
            $this->logger->warning(sprintf(
                "Queue '%s' or its dead-letter queue already existed with different arguments; deleting and redeclaring both with the current arguments.",
                $queue
            ));
            $freshChannel = $this->AMQPStreamConnection->channel();
            $freshChannel->queue_delete($deadLetterQueue);
            $freshChannel->queue_delete($queue);
            $freshChannel->queue_declare($deadLetterQueue, false, true, false, false, false, new AMQPTable(['x-max-length' => self::MAX_QUEUE_LENGTH]));
            $freshChannel->queue_declare($queue, false, true, false, false, false, $arguments);

            return $freshChannel;
        }
    }
}
