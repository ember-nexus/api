<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Command;

use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\Attributes\Group;

/**
 * Executes `cron:reindex-files`, which consumes the queue filled by replaced and deleted files.
 */
#[Group('command')]
class CronReindexFilesTest extends BaseCronTestCase
{
    private const string QUEUE = 'ELASTICSEARCH_REINDEX_FILE';

    private function getQueueMessageCount(): int
    {
        $connection = $this->getRabbitMqConnection();
        $channel = $connection->channel();
        // same arguments as the application uses to declare the queue
        $channel->queue_declare(self::QUEUE, false, false, false, false);
        [, $messageCount] = $channel->queue_declare(self::QUEUE, true);
        $channel->close();
        $connection->close();

        return (int) $messageCount;
    }

    private function publishRawMessage(string $body): void
    {
        $connection = $this->getRabbitMqConnection();
        $channel = $connection->channel();
        $channel->queue_declare(self::QUEUE, false, false, false, false);
        $channel->basic_publish(new AMQPMessage($body), '', self::QUEUE);
        $channel->close();
        $connection->close();
    }

    private function drainQueue(): void
    {
        [$exitCode, $output] = $this->runConsoleCommand('cron:reindex-files');
        $this->assertSame(0, $exitCode, $output);
        // messages which fail are requeued, so poison messages of other tests are removed by their third try
        for ($try = 0; $try < 3 && $this->getQueueMessageCount() > 0; ++$try) {
            $this->runConsoleCommand('cron:reindex-files');
        }
        $this->assertSame(0, $this->getQueueMessageCount());
    }

    private function putFile(string $elementId): void
    {
        $response = $this->runUploadRequest('PUT', sprintf('/%s/file', $elementId), 'reindex me', self::TOKEN, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename=reindex.txt',
        ]);
        $this->assertIsCreatedResponse($response, false);
    }

    public function testQueuedFileIsReindexedAndTheQueueIsEmptyAfterwards(): void
    {
        $this->drainQueue();
        $elementId = $this->createNode('cron-reindex-files');
        $this->putFile($elementId);
        $this->assertGreaterThanOrEqual(1, $this->getQueueMessageCount());

        [$exitCode, $output] = $this->runConsoleCommand('cron:reindex-files');

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString(sprintf('Reindexed file of element %s.', $elementId), $output);
        $this->assertSame(0, $this->getQueueMessageCount());

        [$exitCode, $output] = $this->runConsoleCommand('cron:reindex-files');
        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Reindexed 0 element file(s).', $output);

        $this->deleteNode($elementId);
        $this->drainQueue();
    }

    public function testFileOfElementWhichWasDeletedInTheMeantimeIsSkipped(): void
    {
        $this->drainQueue();
        $elementId = $this->createNode('cron-reindex-deleted-element');
        $this->putFile($elementId);
        $this->deleteNode($elementId);

        [$exitCode, $output] = $this->runConsoleCommand('cron:reindex-files');

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringNotContainsString($elementId, $output);
        $this->assertSame(0, $this->getQueueMessageCount());
        $this->drainQueue();
    }

    public function testDisabledCronLeavesTheQueueUntouched(): void
    {
        $this->drainQueue();
        $elementId = $this->createNode('cron-reindex-disabled');
        $this->putFile($elementId);
        $messageCount = $this->getQueueMessageCount();
        $this->assertGreaterThanOrEqual(1, $messageCount);

        [$exitCode, $output] = $this->runConsoleCommand('cron:reindex-files', ['DISABLE_CRON' => '1']);

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Cron is disabled', $output);
        $this->assertSame($messageCount, $this->getQueueMessageCount());

        $this->deleteNode($elementId);
        $this->drainQueue();
    }

    public function testUnprocessableMessageIsRequeuedTwiceAndDroppedOnTheThirdTry(): void
    {
        $this->drainQueue();
        // valid JSON without the element id the command needs
        $this->publishRawMessage('{"unexpected":"message"}');
        $this->assertSame(1, $this->getQueueMessageCount());

        // the message goes to the end of the queue after the first and the second failed try
        [$exitCode, $output] = $this->runConsoleCommand('cron:reindex-files');
        $this->assertSame(0, $exitCode, $output);
        $this->assertSame(1, $this->getQueueMessageCount());
        [$exitCode, $output] = $this->runConsoleCommand('cron:reindex-files');
        $this->assertSame(0, $exitCode, $output);
        $this->assertSame(1, $this->getQueueMessageCount());

        // third try: dropped
        [$exitCode, $output] = $this->runConsoleCommand('cron:reindex-files');
        $this->assertSame(0, $exitCode, $output);
        $this->assertSame(0, $this->getQueueMessageCount());
    }

    public function testMalformedJsonMessageDoesNotBlockValidMessages(): void
    {
        $this->drainQueue();
        $this->publishRawMessage('this is not json');
        $elementId = $this->createNode('cron-reindex-after-malformed-message');
        $this->putFile($elementId);

        [$exitCode, $output] = $this->runConsoleCommand('cron:reindex-files');

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString(sprintf('Reindexed file of element %s.', $elementId), $output);
        // the malformed message is still waiting for its remaining tries
        $this->assertSame(1, $this->getQueueMessageCount());

        $this->deleteNode($elementId);
        $this->drainQueue();
    }
}
