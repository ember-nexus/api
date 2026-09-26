<?php

declare(strict_types=1);

namespace App\Service;

use App\Type\RabbitMQQueueType;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;
use Throwable;

class QueueService
{
    public const int MAX_TRIES = 3;
    private const string TRY_COUNT_HEADER = 'x-try-count';

    public function __construct(
        private AMQPStreamConnection $AMQPStreamConnection,
        private LoggerInterface $logger,
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
     * Messages are acknowledged once $handler returns without throwing. A message which fails (or can not be decoded) is
     * logged, republished at the end of the queue with an increased try counter (after the queue was drained, so it is
     * not consumed again in this run) and acknowledged; after MAX_TRIES failed tries it is dropped and this is logged.
     * Returns the number of successfully processed messages.
     */
    public function consumeQueue(RabbitMQQueueType $queueType, callable $handler): int
    {
        $channel = $this->AMQPStreamConnection->channel();
        $queue = $queueType->value;
        $channel->queue_declare($queue, false, false, false, false);

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

        foreach ($messagesToRequeue as $messageToRequeue) {
            $channel->basic_publish(
                new AMQPMessage($messageToRequeue['body'], [
                    'application_headers' => new AMQPTable([self::TRY_COUNT_HEADER => $messageToRequeue['tries']]),
                ]),
                '',
                $queue
            );
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
}
