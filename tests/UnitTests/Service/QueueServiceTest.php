<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Service;

use App\Service\QueueService;
use App\Type\RabbitMQQueueType;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPProtocolChannelException;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use RuntimeException;

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

        $queueService = new QueueService($connection->reveal(), $this->prophesize(LoggerInterface::class)->reveal());
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

        $queueService = new QueueService($connection->reveal(), $this->prophesize(LoggerInterface::class)->reveal());
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

        $queueService = new QueueService($connection->reveal(), $this->prophesize(LoggerInterface::class)->reveal());
        $processedMessages = $queueService->consumeQueue(
            RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE,
            function (array $eventData) use (&$handledElementIds): void {
                $handledElementIds[] = $eventData['elementId'];
            }
        );

        $this->assertSame(2, $processedMessages);
        $this->assertSame(['1', '2'], $handledElementIds);
    }

    public function testConsumeQueueRequeuesFailingMessageWithIncreasedTryCounterAfterDrainingQueue(): void
    {
        $failingMessage = new AMQPMessage('{"elementId":"bad"}');
        $goodMessage = new AMQPMessage('{"elementId":"good"}');

        $channel = $this->prophesize(AMQPChannel::class);
        $channel->queue_declare('ELASTICSEARCH_REINDEX_FILE', false, false, false, false)->shouldBeCalledOnce();
        $channel->basic_get('ELASTICSEARCH_REINDEX_FILE')->shouldBeCalledTimes(3)->willReturn($failingMessage, $goodMessage, null);
        $channel->basic_publish(
            Argument::that(fn (AMQPMessage $message) => '{"elementId":"bad"}' === $message->getBody()
                && 1 === $message->get('application_headers')->getNativeData()['x-try-count']),
            '',
            'ELASTICSEARCH_REINDEX_FILE'
        )->shouldBeCalledOnce();
        $channel->close()->shouldBeCalledOnce();
        $failingMessage->setChannel($channel->reveal());
        $goodMessage->setChannel($channel->reveal());
        $channel->basic_ack(Argument::any(), Argument::any())->shouldBeCalledTimes(2);

        $connection = $this->prophesize(AMQPStreamConnection::class);
        $connection->channel()->willReturn($channel->reveal());

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(Argument::containingString('try 1 of 3'))->shouldBeCalledOnce();

        $queueService = new QueueService($connection->reveal(), $logger->reveal());
        $processedMessages = $queueService->consumeQueue(
            RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE,
            function (array $eventData): void {
                if ('bad' === $eventData['elementId']) {
                    throw new RuntimeException('boom');
                }
            }
        );

        $this->assertSame(1, $processedMessages);
    }

    public function testConsumeQueueDropsMessageAfterThirdFailedTry(): void
    {
        $message = new AMQPMessage('{"elementId":"bad"}', [
            'application_headers' => new AMQPTable(['x-try-count' => 2]),
        ]);

        $channel = $this->prophesize(AMQPChannel::class);
        $channel->queue_declare(Argument::cetera())->shouldBeCalledOnce();
        $channel->basic_get('ELASTICSEARCH_REINDEX_FILE')->willReturn($message, null);
        $channel->basic_publish(Argument::cetera())->shouldNotBeCalled();
        $channel->close()->shouldBeCalledOnce();
        $message->setChannel($channel->reveal());
        $channel->basic_ack(Argument::any(), Argument::any())->shouldBeCalledOnce();

        $connection = $this->prophesize(AMQPStreamConnection::class);
        $connection->channel()->willReturn($channel->reveal());

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(Argument::containingString('Dropping message'))->shouldBeCalledOnce();

        $queueService = new QueueService($connection->reveal(), $logger->reveal());
        $this->assertSame(0, $queueService->consumeQueue(
            RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE,
            function (): void {
                throw new RuntimeException('boom');
            }
        ));
    }

    public function testPublishEventDeclaresDurableQueueWithArgumentsAndPersistentMessageForOwnershipQueue(): void
    {
        $channel = $this->prophesize(AMQPChannel::class);
        $channel->queue_declare('ELASTICSEARCH_UPDATE_OWNERSHIP.dead-letter', false, true, false, false, false, Argument::type(AMQPTable::class))->shouldBeCalledOnce();
        $channel->queue_declare('ELASTICSEARCH_UPDATE_OWNERSHIP', false, true, false, false, false, Argument::that(
            function (AMQPTable $table) {
                $data = $table->getNativeData();

                return 100_000 === $data['x-max-length']
                    && '' === $data['x-dead-letter-exchange']
                    && 'ELASTICSEARCH_UPDATE_OWNERSHIP.dead-letter' === $data['x-dead-letter-routing-key'];
            }
        ))->shouldBeCalledOnce();
        $channel->basic_publish(
            Argument::that(fn (AMQPMessage $message) => AMQPMessage::DELIVERY_MODE_PERSISTENT === $message->get('delivery_mode')),
            '',
            'ELASTICSEARCH_UPDATE_OWNERSHIP'
        )->shouldBeCalledOnce();
        $channel->close()->shouldBeCalledOnce();

        $connection = $this->prophesize(AMQPStreamConnection::class);
        $connection->channel()->willReturn($channel->reveal());

        $queueService = new QueueService($connection->reveal(), $this->prophesize(LoggerInterface::class)->reveal());
        $queueService->publishEvent(RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE, ['relationId' => 'abc']);
    }

    public function testDeclareQueueRecoversFromPreconditionFailedByRedeclaringOnAFreshChannel(): void
    {
        $failingChannel = $this->prophesize(AMQPChannel::class);
        $failingChannel->queue_declare('ELASTICSEARCH_UPDATE_OWNERSHIP.dead-letter', false, true, false, false, false, Argument::type(AMQPTable::class))
            ->willThrow(new AMQPProtocolChannelException(406, 'PRECONDITION_FAILED', [10, 50]));

        $freshChannel = $this->prophesize(AMQPChannel::class);
        $freshChannel->queue_delete('ELASTICSEARCH_UPDATE_OWNERSHIP.dead-letter')->shouldBeCalledOnce();
        $freshChannel->queue_delete('ELASTICSEARCH_UPDATE_OWNERSHIP')->shouldBeCalledOnce();
        $freshChannel->queue_declare('ELASTICSEARCH_UPDATE_OWNERSHIP.dead-letter', false, true, false, false, false, Argument::type(AMQPTable::class))->shouldBeCalledOnce();
        $freshChannel->queue_declare('ELASTICSEARCH_UPDATE_OWNERSHIP', false, true, false, false, false, Argument::type(AMQPTable::class))->shouldBeCalledOnce();
        $freshChannel->basic_publish(Argument::cetera())->shouldBeCalledOnce();
        $freshChannel->close()->shouldBeCalledOnce();

        $connection = $this->prophesize(AMQPStreamConnection::class);
        $connection->channel()->willReturn($failingChannel->reveal(), $freshChannel->reveal());

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->warning(Argument::containingString('ELASTICSEARCH_UPDATE_OWNERSHIP'))->shouldBeCalledOnce();

        $queueService = new QueueService($connection->reveal(), $logger->reveal());
        $queueService->publishEvent(RabbitMQQueueType::ELASTICSEARCH_UPDATE_OWNERSHIP_QUEUE, ['relationId' => 'abc']);
    }

    public function testConsumeQueueTreatsMalformedJsonAsFailedMessage(): void
    {
        $message = new AMQPMessage('not json');

        $channel = $this->prophesize(AMQPChannel::class);
        $channel->queue_declare(Argument::cetera())->shouldBeCalledOnce();
        $channel->basic_get('ELASTICSEARCH_REINDEX_FILE')->willReturn($message, null);
        $channel->basic_publish(Argument::cetera())->shouldBeCalledOnce();
        $channel->close()->shouldBeCalledOnce();
        $message->setChannel($channel->reveal());
        $channel->basic_ack(Argument::any(), Argument::any())->shouldBeCalledOnce();

        $connection = $this->prophesize(AMQPStreamConnection::class);
        $connection->channel()->willReturn($channel->reveal());

        $logger = $this->prophesize(LoggerInterface::class);
        $logger->error(Argument::any())->shouldBeCalledOnce();

        $queueService = new QueueService($connection->reveal(), $logger->reveal());
        $this->assertSame(0, $queueService->consumeQueue(
            RabbitMQQueueType::ELASTICSEARCH_REINDEX_FILE_QUEUE,
            function (): void {
                $this->fail('Handler must not be called for undecodable messages.');
            }
        ));
    }
}
