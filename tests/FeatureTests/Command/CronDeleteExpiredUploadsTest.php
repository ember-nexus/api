<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Command;

use App\Factory\Type\RedisKeyFactory;
use PHPUnit\Framework\Attributes\Group;

/**
 * Executes `cron:delete-expired-uploads` as it runs in production. The default grace period after the expiration of an
 * upload is one hour.
 */
#[Group('command')]
class CronDeleteExpiredUploadsTest extends BaseCronTestCase
{
    private function getAttemptKey(string $uploadId): string
    {
        return (string) (new RedisKeyFactory())->getCronDeleteExpiredUploadRedisKey($uploadId);
    }

    /**
     * An element can only be the target of one unfinished upload at a time.
     *
     * @return array{0: string, 1: string} element id and upload id
     */
    private function createElementWithUpload(string $name): array
    {
        $elementId = $this->createNode($name);

        return [$elementId, $this->createUpload($elementId)];
    }

    public function testOnlyUploadsWhichExpiredLongerThanTheGracePeriodAgoAreDeleted(): void
    {
        [$expiredElementId, $expiredUploadId] = $this->createElementWithUpload('cron-delete-expired-uploads-expired');
        [$withinGracePeriodElementId, $withinGracePeriodUploadId] = $this->createElementWithUpload('cron-delete-expired-uploads-grace');
        [$activeElementId, $activeUploadId] = $this->createElementWithUpload('cron-delete-expired-uploads-active');
        $this->setUploadExpiration($expiredUploadId, "datetime() - duration('PT2H')");
        // expired, but not yet for longer than the grace period of one hour
        $this->setUploadExpiration($withinGracePeriodUploadId, "datetime() - duration('PT10M')");
        $this->assertSame(1, $this->countUploadChunksInUploadBucket($expiredUploadId));

        [$exitCode, $output] = $this->runConsoleCommand('cron:delete-expired-uploads');

        $this->assertSame(0, $exitCode, $output);
        $this->assertMatchesRegularExpression('/Deleted [1-9]\d* expired upload\(s\)/', $output);
        $this->assertFalse($this->uploadNodeExists($expiredUploadId));
        $this->assertSame(0, $this->countUploadChunksInUploadBucket($expiredUploadId));
        $this->assertTrue($this->uploadNodeExists($withinGracePeriodUploadId));
        $this->assertSame(1, $this->countUploadChunksInUploadBucket($withinGracePeriodUploadId));
        $this->assertTrue($this->uploadNodeExists($activeUploadId));
        $this->assertSame(204, $this->runHeadRequest(sprintf('/upload/%s', $activeUploadId), self::TOKEN)->getStatusCode());
        // the elements themselves are untouched
        $this->assertSame(200, $this->runGetRequest(sprintf('/%s', $expiredElementId), self::TOKEN)->getStatusCode());

        // deleting an element removes its remaining uploads and their chunks
        $this->deleteNode($expiredElementId);
        $this->deleteNode($withinGracePeriodElementId);
        $this->deleteNode($activeElementId);
        $this->assertFalse($this->uploadNodeExists($withinGracePeriodUploadId));
        $this->assertFalse($this->uploadNodeExists($activeUploadId));
        $this->assertSame(0, $this->countUploadChunksInUploadBucket($withinGracePeriodUploadId));
        $this->assertSame(0, $this->countUploadChunksInUploadBucket($activeUploadId));
    }

    public function testDisabledCronDoesNotDeleteAnything(): void
    {
        [$elementId, $uploadId] = $this->createElementWithUpload('cron-disabled');
        $this->setUploadExpiration($uploadId, "datetime() - duration('PT2H')");

        [$exitCode, $output] = $this->runConsoleCommand('cron:delete-expired-uploads', ['DISABLE_CRON' => '1']);

        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('Cron is disabled', $output);
        $this->assertTrue($this->uploadNodeExists($uploadId));
        $this->assertSame(1, $this->countUploadChunksInUploadBucket($uploadId));

        $this->deleteNode($elementId);
    }

    public function testBrokenUploadDoesNotBlockOthersIsRetriedWithBackoffAndGivenUpAfterThreeAttempts(): void
    {
        [$brokenElementId, $brokenUploadId] = $this->createElementWithUpload('cron-broken-upload');
        [$healthyElementId, $healthyUploadId] = $this->createElementWithUpload('cron-healthy-upload');
        $this->setUploadExpiration($brokenUploadId, "datetime() - duration('PT2H')");
        $this->setUploadExpiration($healthyUploadId, "datetime() - duration('PT2H')");
        // an upload which references a non-existing target can not be turned into an upload object
        $this->setUploadProperty($brokenUploadId, "u.uploadTarget = 'not-a-uuid'");
        $redis = $this->getRedisClient();
        $attemptKey = $this->getAttemptKey($brokenUploadId);
        $redis->del([$attemptKey]);

        // first attempt: fails, is remembered with a delay of one hour; the healthy upload is deleted anyway
        [$exitCode, $output] = $this->runConsoleCommand('cron:delete-expired-uploads');
        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString($brokenUploadId, $output);
        $this->assertStringContainsString('will retry later', $output);
        $this->assertFalse($this->uploadNodeExists($healthyUploadId));
        $this->assertTrue($this->uploadNodeExists($brokenUploadId));
        $state = \Safe\json_decode((string) $redis->get($attemptKey), true);
        $this->assertSame(1, $state['attempts']);
        $this->assertGreaterThan(time() + 3000, $state['notBefore']);

        // second run: the upload waits for its retry, nothing is attempted or logged as failure
        [$exitCode, $output] = $this->runConsoleCommand('cron:delete-expired-uploads');
        $this->assertSame(0, $exitCode, $output);
        $this->assertStringNotContainsString('Failed to delete', $output);
        $this->assertMatchesRegularExpression('/[1-9]\d* waiting for a retry/', $output);
        $this->assertSame(1, \Safe\json_decode((string) $redis->get($attemptKey), true)['attempts']);

        // the delay passes, second failed attempt
        $redis->set($attemptKey, \Safe\json_encode(['attempts' => 1, 'notBefore' => time() - 10]), 'EX', 600);
        [$exitCode, $output] = $this->runConsoleCommand('cron:delete-expired-uploads');
        $this->assertSame(0, $exitCode, $output);
        $this->assertStringContainsString('will retry later', $output);
        $state = \Safe\json_decode((string) $redis->get($attemptKey), true);
        $this->assertSame(2, $state['attempts']);
        $this->assertGreaterThan(time() + 80000, $state['notBefore']);
        $this->assertTrue($this->uploadNodeExists($brokenUploadId));

        // third failure is final: the upload node is removed and the attempts are forgotten
        $redis->set($attemptKey, \Safe\json_encode(['attempts' => 2, 'notBefore' => time() - 10]), 'EX', 600);
        [$exitCode, $output] = $this->runConsoleCommand('cron:delete-expired-uploads');
        $this->assertSame(0, $exitCode, $output);
        $this->assertMatchesRegularExpression('/[1-9]\d* given up/', $output);
        $this->assertFalse($this->uploadNodeExists($brokenUploadId));
        $this->assertNull($redis->get($attemptKey));

        $this->deleteNode($brokenElementId);
        $this->deleteNode($healthyElementId);
    }
}
