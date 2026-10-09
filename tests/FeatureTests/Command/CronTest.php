<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Command;

use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\Attributes\Group;

/**
 * Executes the umbrella `cron` command, which dispatches `cron:delete-expired-uploads`, `cron:reindex-files` and
 * `cron:update-ownership` in sequence. Each task's own business logic is already covered by
 * {@see CronDeleteExpiredUploadsTest}, {@see CronReindexFilesTest} and {@see CronUpdateOwnershipTest}; this class
 * only covers the orchestration itself - that `cron` actually dispatches all three tasks end to end, and that
 * disabling cron skips all of them before any task runs.
 */
#[Group('command')]
class CronTest extends BaseCronTestCase
{
    private const string REINDEX_QUEUE = 'ELASTICSEARCH_REINDEX_FILE';
    private const string OWNERSHIP_QUEUE = 'ELASTICSEARCH_UPDATE_OWNERSHIP';
    private const string OWNERSHIP_DEAD_LETTER_QUEUE = 'ELASTICSEARCH_UPDATE_OWNERSHIP.dead-letter';
    private const int OWNERSHIP_QUEUE_MAX_LENGTH = 100_000;

    private function declareOwnershipQueues(): void
    {
        $connection = $this->getRabbitMqConnection();
        $channel = $connection->channel();
        // same arguments as QueueService uses to declare this (durable, bounded, dead-lettered) queue
        $channel->queue_declare(self::OWNERSHIP_DEAD_LETTER_QUEUE, false, true, false, false, false, new AMQPTable([
            'x-max-length' => self::OWNERSHIP_QUEUE_MAX_LENGTH,
        ]));
        $channel->queue_declare(self::OWNERSHIP_QUEUE, false, true, false, false, false, new AMQPTable([
            'x-max-length' => self::OWNERSHIP_QUEUE_MAX_LENGTH,
            'x-dead-letter-exchange' => '',
            'x-dead-letter-routing-key' => self::OWNERSHIP_DEAD_LETTER_QUEUE,
        ]));
        $channel->close();
        $connection->close();
    }

    private function getReindexQueueMessageCount(): int
    {
        $connection = $this->getRabbitMqConnection();
        $channel = $connection->channel();
        $channel->queue_declare(self::REINDEX_QUEUE, false, false, false, false);
        [, $messageCount] = $channel->queue_declare(self::REINDEX_QUEUE, true);
        $channel->close();
        $connection->close();

        return (int) $messageCount;
    }

    private function getOwnershipQueueMessageCount(): int
    {
        $this->declareOwnershipQueues();

        $connection = $this->getRabbitMqConnection();
        $channel = $connection->channel();
        [, $messageCount] = $channel->queue_declare(self::OWNERSHIP_QUEUE, true);
        $channel->close();
        $connection->close();

        return (int) $messageCount;
    }

    private function drainViaCron(): void
    {
        [$exitCode, $output] = $this->runConsoleCommand('cron');
        $this->assertSame(0, $exitCode, $output);
        // messages which fail are requeued, so poison messages of other tests are removed by their third try
        for ($try = 0; $try < 3 && ($this->getReindexQueueMessageCount() > 0 || $this->getOwnershipQueueMessageCount() > 0); ++$try) {
            $this->runConsoleCommand('cron');
        }
        $this->assertSame(0, $this->getReindexQueueMessageCount());
        $this->assertSame(0, $this->getOwnershipQueueMessageCount());
    }

    private function putFile(string $elementId): void
    {
        $response = $this->runUploadRequest('PUT', sprintf('/%s/file', $elementId), 'reindex me via cron', self::TOKEN, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename=reindex.txt',
        ]);
        $this->assertIsCreatedResponse($response, false);
    }

    /**
     * Both endpoints are created by self::TOKEN's own user, so it already has CREATE access on the start and READ
     * access on the end without any extra setup - unlike {@see CronUpdateOwnershipTest}, which needs a second user
     * to verify the resulting *access*, this only needs the relation change to be queued and drained without error.
     */
    private function createOwnsRelation(string $startId, string $endId): string
    {
        $response = $this->runPostRequest('/', self::TOKEN, [
            'type' => 'OWNS',
            'start' => $startId,
            'end' => $endId,
        ]);
        $this->assertIsCreatedResponse($response);

        return $this->getUuidFromLocation($response);
    }

    public function testCronDispatchesAllThreeTasksAndReportsSuccess(): void
    {
        $this->drainViaCron();

        // material for cron:delete-expired-uploads
        $expiredElementId = $this->createNode('cron-umbrella-expired-upload');
        $expiredUploadId = $this->createUpload($expiredElementId);
        $this->setUploadExpiration($expiredUploadId, "datetime() - duration('PT2H')");

        // material for cron:reindex-files
        $reindexElementId = $this->createNode('cron-umbrella-reindex');
        $this->putFile($reindexElementId);
        $this->assertGreaterThanOrEqual(1, $this->getReindexQueueMessageCount());

        // material for cron:update-ownership
        $ownershipStartId = $this->createNode('cron-umbrella-ownership-start');
        $ownershipEndId = $this->createNode('cron-umbrella-ownership-end');
        $ownershipRelationId = $this->createOwnsRelation($ownershipStartId, $ownershipEndId);
        $this->assertGreaterThanOrEqual(1, $this->getOwnershipQueueMessageCount());

        [$exitCode, $output] = $this->runConsoleCommand('cron');

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Finished.', $output);
        $this->assertMatchesRegularExpression('/Deleted [1-9]\d* expired upload\(s\)/', $output);
        $this->assertStringContainsString(sprintf('Reindexed file of element %s.', $reindexElementId), $output);
        $this->assertMatchesRegularExpression('/updated search access of [1-9]\d* element\(s\)/', $output);
        $this->assertFalse($this->uploadNodeExists($expiredUploadId));
        $this->assertSame(0, $this->getReindexQueueMessageCount());
        $this->assertSame(0, $this->getOwnershipQueueMessageCount());

        $this->deleteNode($ownershipRelationId);
        $this->deleteNode($expiredElementId);
        $this->deleteNode($reindexElementId);
        $this->deleteNode($ownershipStartId);
        $this->deleteNode($ownershipEndId);
        $this->drainViaCron();
    }

    public function testDisabledCronTerminatesEarlyAndLeavesAllThreeTasksUntouched(): void
    {
        $this->drainViaCron();

        $expiredElementId = $this->createNode('cron-umbrella-disabled-expired-upload');
        $expiredUploadId = $this->createUpload($expiredElementId);
        $this->setUploadExpiration($expiredUploadId, "datetime() - duration('PT2H')");

        $reindexElementId = $this->createNode('cron-umbrella-disabled-reindex');
        $this->putFile($reindexElementId);
        $reindexMessageCount = $this->getReindexQueueMessageCount();
        $this->assertGreaterThanOrEqual(1, $reindexMessageCount);

        $ownershipStartId = $this->createNode('cron-umbrella-disabled-ownership-start');
        $ownershipEndId = $this->createNode('cron-umbrella-disabled-ownership-end');
        $ownershipRelationId = $this->createOwnsRelation($ownershipStartId, $ownershipEndId);
        $ownershipMessageCount = $this->getOwnershipQueueMessageCount();
        $this->assertGreaterThanOrEqual(1, $ownershipMessageCount);

        [$exitCode, $output] = $this->runConsoleCommand('cron', ['DISABLE_CRON' => '1']);

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Cron is disabled', $output);
        $this->assertTrue($this->uploadNodeExists($expiredUploadId));
        $this->assertSame($reindexMessageCount, $this->getReindexQueueMessageCount());
        $this->assertSame($ownershipMessageCount, $this->getOwnershipQueueMessageCount());

        $this->deleteNode($ownershipRelationId);
        $this->deleteNode($expiredElementId);
        $this->deleteNode($reindexElementId);
        $this->deleteNode($ownershipStartId);
        $this->deleteNode($ownershipEndId);
        $this->drainViaCron();
    }
}
