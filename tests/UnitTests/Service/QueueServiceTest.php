<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Service\QueueService;
use App\Type\RabbitMQQueueType;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

#[Small]
#[CoversClass(QueueService::class)]
class QueueServiceTest extends TestCase
{
    use ProphecyTrait;

    public function testPublishEventPublishesJsonEncodedMessageAndClosesChannel(): void
    {
        $channel = $this->prophesize(AMQPChannel::class);
        $channel->queue_declare('ELASTICSEARCH_REINDEX_FILE', false, false, false, false)->shouldBeCalledOnce();
        $channel->basic_publish(
            Argument::that(fn (AMQPMessage $message) => '{"elementId":"abc"}' === $message->getBody()),
            '',
            'ELASTICSEARCH_REINDEX_FILE'
        )->shouldBeCalledOnce();
        $channel->close()->shouldBeCalledOnce();

        $connection = $this->prophesize(AMQPStreamConnection::class);
        $connection->channel()->willReturn($channel->reveal());

        $queueService = new QueueService($connection->reveal());
        $queueService->publishEvent(RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE, ['elementId' => 'abc']);
    }

    public function testConsumeQueueReturnsZeroForEmptyQueueWithoutCallingHandler(): void
    {
        $channel = $this->prophesize(AMQPChannel::class);
        $channel->queue_declare('ELASTICSEARCH_REINDEX_FILE', false, false, false, false)->shouldBeCalledOnce();
        $channel->basic_get('ELASTICSEARCH_REINDEX_FILE')->shouldBeCalledOnce()->willReturn(null);
        $channel->close()->shouldBeCalledOnce();

        $connection = $this->prophesize(AMQPStreamConnection::class);
        $connection->channel()->willReturn($channel->reveal());

        $queueService = new QueueService($connection->reveal());
        $processedMessages = $queueService->consumeQueue(
            RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE,
            function (): void {
                $this->fail('Handler should not be called for an empty queue.');
            }
        );

        $this->assertSame(0, $processedMessages);
    }

    public function testConsumeQueueDrainsAllMessagesAndAcknowledgesEach(): void
    {
        $message1 = new AMQPMessage('{"elementId":"1"}');
        $message2 = new AMQPMessage('{"elementId":"2"}');

        $channel = $this->prophesize(AMQPChannel::class);
        $channel->queue_declare('ELASTICSEARCH_REINDEX_FILE', false, false, false, false)->shouldBeCalledOnce();
        $channel->basic_get('ELASTICSEARCH_REINDEX_FILE')->willReturn($message1, $message2, null);
        $channel->close()->shouldBeCalledOnce();

        $connection = $this->prophesize(AMQPStreamConnection::class);
        $connection->channel()->willReturn($channel->reveal());

        $message1->setChannel($channel->reveal());
        $message2->setChannel($channel->reveal());
        $channel->basic_ack(Argument::any(), Argument::any())->shouldBeCalledTimes(2);

        $handledElementIds = [];

        $queueService = new QueueService($connection->reveal());
        $processedMessages = $queueService->consumeQueue(
            RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE,
            function (array $eventData) use (&$handledElementIds): void {
                $handledElementIds[] = $eventData['elementId'];
            }
        );

        $this->assertSame(2, $processedMessages);
        $this->assertSame(['1', '2'], $handledElementIds);
    }
}
