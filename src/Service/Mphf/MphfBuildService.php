<?php

declare(strict_types=1);

namespace App\Service\Mphf;

use App\Factory\Type\RedisKeyFactory;
use App\Helper\CypherHelper;
use App\Service\GraphStructureService;
use App\Service\Index\IndexEnsurer;
use App\Service\MaintenanceLockService;
use App\Type\ElementKind;
use App\Type\MaintenanceLock;
use App\Type\MaintenanceLockType;
use App\Type\MphfOverlaySlot;
use App\Type\ResolvedType;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Process\Process;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Throwable;

use function Safe\fclose;
use function Safe\filesize;
use function Safe\fopen;
use function Safe\fwrite;
use function Safe\glob;
use function Safe\mkdir;
use function Safe\rmdir;
use function Safe\unlink;

/**
 * Builds a new type index (MPHF) from the graph and publishes it.
 *
 * Order matters: the overlay is switched first, so every element created from now on is found in the (new) active
 * overlay key; the build then covers everything created before. Only after the new version is published the old
 * overlay key (whose entries the build covers) is deleted. The element id filter is built the same way, from the same
 * export: it is switched together with the overlay, filled while the ids are exported, and trusted when it is complete.
 */
class MphfBuildService
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private GraphStructureService $graphStructureService,
        private IndexEnsurer $indexEnsurer,
        private MaintenanceLockService $maintenanceLockService,
        private ElementTypeOverlayService $overlayService,
        private ElementIdFilterService $elementIdFilterService,
        private MphfStorageService $storageService,
        private SystemBucketService $systemBucketService,
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyFactory,
        private LoggerInterface $logger,
        private int $mphfOverlaySwitchDelay,
        private int $mphfOverlayClearDelay,
        private int $mphfRetentionFiles,
        private int $mphfExportBatchSize,
        private string $mphfBuilderBinary,
        private string $appVersion,
    ) {
    }

    /**
     * @param callable(string): void|null $progress           receives human readable progress lines
     * @param bool                        $concurrentRequests false if nothing can write while the build runs (e.g. a backup
     *                                                        is loaded into an instance without traffic); skips the delays
     *                                                        which protect requests in flight
     *
     * @throws Exception if the build failed; the lock is released and nothing was published (or the new file stays
     *                   unreferenced in the system bucket, which is harmless)
     */
    public function build(?callable $progress = null, bool $concurrentRequests = true): MphfBuildResult
    {
        $progress ??= static function (): void {};
        $lock = $this->maintenanceLockService->claim(MaintenanceLockType::MPHF);
        if (null === $lock) {
            return MphfBuildResult::skipped('another process holds lock:maintenance:mphf, a build is running already');
        }
        $workDirectory = '';
        try {
            if ($this->maintenanceLockService->isHeld(MaintenanceLockType::DATABASE)) {
                return MphfBuildResult::skipped('backup load or reset in progress');
            }

            return $this->buildWithLock($lock, $progress, $workDirectory, $concurrentRequests);
        } finally {
            if ('' !== $workDirectory) {
                $this->removeDirectory($workDirectory);
            }
            $this->maintenanceLockService->release($lock);
        }
    }

    /**
     * @param callable(string): void $progress
     */
    private function buildWithLock(MaintenanceLock $lock, callable $progress, string &$workDirectory, bool $concurrentRequests): MphfBuildResult
    {
        $start = microtime(true);
        $version = gmdate('Ymd-Hi').'.mphf';
        $this->systemBucketService->ensureBucketExists();

        $progress('Switching the Redis overlay, new elements are tracked there from now on.');
        $this->overlayService->switchActive();
        $switchedAt = microtime(true);
        $filterSlot = $this->prepareElementIdFilter($progress);

        $progress('Making sure id indices exist.');
        $skipReason = null;
        $this->indexEnsurer->ensureWithLock(awaitSeconds: IndexEnsurer::DEFAULT_AWAIT_SECONDS, skipReason: $skipReason);
        if (null !== $skipReason) {
            $progress(sprintf('Index creation not done by this run: %s.', $skipReason));
        }

        // Requests which started before the switch may still register an element in the previously active key and
        // commit it only later; the export has to start after they are done. Work done since the switch counts.
        $remaining = $concurrentRequests ? $this->mphfOverlaySwitchDelay - (microtime(true) - $switchedAt) : 0;
        if ($remaining > 0) {
            usleep((int) ceil($remaining * 1_000_000));
        }

        $workDirectory = sys_get_temp_dir().'/ember-nexus-mphf-build-'.bin2hex(random_bytes(6));
        mkdir($workDirectory, 0o700, true);
        $csvPath = $workDirectory.'/ids.csv';
        $outputPath = $workDirectory.'/'.$version;

        $progress('Exporting element ids.');
        $keyCount = $this->exportIds($csvPath, $lock, $progress, $filterSlot);

        $progress(sprintf('Building the index from %d ids.', $keyCount));
        $this->runBuilder($csvPath, $outputPath, $version);
        unlink($csvPath);
        $size = filesize($outputPath);

        if (!$this->maintenanceLockService->isStillOwned($lock)) {
            throw new RuntimeException('The lock lock:maintenance:mphf was lost during the build, the result is not published.');
        }
        $progress('Uploading to the system bucket.');
        $this->systemBucketService->upload($this->storageService->getBucketKey($version), $outputPath);
        $this->redisClient->set((string) $this->redisKeyFactory->getMphfVersionRedisKey(), $version);

        if ($concurrentRequests && $this->mphfOverlayClearDelay > 0) {
            // Processes need a moment to pick up the new version, until then they still need the entries it covers.
            // Redis removes them later on its own, the build does not wait.
            $this->overlayService->expireInactive($this->mphfOverlayClearDelay);
        } else {
            $this->overlayService->clearInactive();
        }
        if (null !== $filterSlot) {
            $this->elementIdFilterService->finishBuild($filterSlot);
            $progress('Element id filter is complete and in use.');
        }

        $deleted = $this->applyRetention($version);

        return new MphfBuildResult(false, null, $version, $keyCount, $size, microtime(true) - $start, $deleted);
    }

    /**
     * Starts the element id filter, which is filled while the ids are exported. The switch has to happen together with
     * the one of the overlay, so that the delay below covers both. A Redis which can't do Bloom filters (or a disabled
     * filter) does not fail the build, the duplicate id check just keeps asking the database.
     *
     * @param callable(string): void $progress
     */
    private function prepareElementIdFilter(callable $progress): ?MphfOverlaySlot
    {
        if (!$this->elementIdFilterService->isEnabled()) {
            return null;
        }
        try {
            $capacity = intdiv(6 * $this->countElements(), 5); // with a reserve of 20 %

            return $this->elementIdFilterService->prepareBuild($capacity);
        } catch (Throwable $throwable) {
            $this->logger->warning('Unable to create the element id filter.', ['exception' => $throwable]);
            $progress(sprintf('Element id filter skipped, Redis can not create it: %s', $throwable->getMessage()));

            return null;
        }
    }

    private function countElements(): int
    {
        $client = $this->cypherEntityManager->getClient();
        $nodes = $client->runStatement(Statement::create('MATCH (n) RETURN count(n) AS count'))->first()->get('count');
        $relations = $client->runStatement(Statement::create('MATCH ()-[r]->() RETURN count(r) AS count'))->first()->get('count');

        return (is_int($nodes) ? $nodes : 0) + (is_int($relations) ? $relations : 0);
    }

    /**
     * @param callable(string): void $progress
     */
    private function exportIds(string $csvPath, MaintenanceLock $lock, callable $progress, ?MphfOverlaySlot $filterSlot): int
    {
        $this->graphStructureService->loadGraphStructure();
        $handle = fopen($csvPath, 'wb');
        $count = 0;
        try {
            foreach ($this->graphStructureService->getNodeTypes() as $label) {
                $escaped = CypherHelper::escapeIdentifier($label);
                // labels(n)[0] is the label the rest of the application treats as the type of the node
                $count += $this->exportType(
                    $handle,
                    $label,
                    (new ResolvedType(ElementKind::NODE, $label))->encode(),
                    sprintf('MATCH (e:%s) WHERE e.id > $last AND labels(e)[0] = $type RETURN e.id AS id ORDER BY e.id LIMIT $limit', $escaped),
                    $lock,
                    $filterSlot
                );
            }
            foreach ($this->graphStructureService->getRelationTypes() as $type) {
                $escaped = CypherHelper::escapeIdentifier($type);
                $count += $this->exportType(
                    $handle,
                    $type,
                    (new ResolvedType(ElementKind::RELATION, $type))->encode(),
                    sprintf('MATCH ()-[e:%s]->() WHERE e.id > $last RETURN e.id AS id ORDER BY e.id LIMIT $limit', $escaped),
                    $lock,
                    $filterSlot
                );
            }
        } finally {
            fclose($handle);
        }
        $progress(sprintf('Exported %d ids.', $count));

        return $count;
    }

    /**
     * Keyset pagination on the id (index backed), not SKIP, so the cost per batch stays constant.
     *
     * @param resource $handle
     */
    private function exportType(mixed $handle, string $type, string $storedType, string $cypher, MaintenanceLock $lock, ?MphfOverlaySlot $filterSlot): int
    {
        $last = '';
        $count = 0;
        do {
            $result = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
                $cypher,
                ['last' => $last, 'limit' => $this->mphfExportBatchSize, 'type' => $type]
            ));
            $rows = 0;
            $ids = [];
            foreach ($result as $row) {
                $id = $row->get('id');
                if (!is_string($id)) {
                    continue;
                }
                fwrite($handle, $id.';'.$storedType."\n");
                $ids[] = $id;
                $last = $id;
                ++$rows;
            }
            if (null !== $filterSlot) {
                $this->elementIdFilterService->addMany($filterSlot, $ids);
            }
            $count += $rows;
            $this->maintenanceLockService->refresh($lock);
        } while ($rows >= $this->mphfExportBatchSize);

        return $count;
    }

    private function runBuilder(string $csvPath, string $outputPath, string $version): void
    {
        $watermark = (string) (int) (microtime(true) * 1000.0);
        $process = new Process([$this->mphfBuilderBinary, 'build', '--input', $csvPath, '--output', $outputPath, '--watermark-ms', $watermark, '--generator', 'Ember Nexus API '.$this->appVersion]);
        $process->setTimeout(null);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf('mphf-builder failed for %s: %s', $version, trim($process->getErrorOutput())));
        }
        if (!$this->storageService->hasValidChecksum($outputPath)) {
            throw new RuntimeException('The built file does not pass its checksum.');
        }
        $this->logger->info('Built type index.', ['version' => $version, 'output' => trim($process->getOutput())]);
    }

    /**
     * Keeps the newest files (the new version is the newest by name), deletes the rest from the system bucket.
     */
    private function applyRetention(string $currentVersion): int
    {
        $keys = $this->systemBucketService->list(MphfStorageService::BUCKET_PREFIX);
        $versions = [];
        foreach ($keys as $key) {
            $name = substr($key, strlen(MphfStorageService::BUCKET_PREFIX));
            if ($this->storageService->isValidVersion($name)) {
                $versions[] = $name;
            }
        }
        rsort($versions);
        $keep = array_slice($versions, 0, max(1, $this->mphfRetentionFiles));
        if (!in_array($currentVersion, $keep, true)) {
            $keep[] = $currentVersion;
        }
        $deleted = 0;
        foreach ($versions as $name) {
            if (!in_array($name, $keep, true)) {
                $this->systemBucketService->delete($this->storageService->getBucketKey($name));
                ++$deleted;
            }
        }

        return $deleted;
    }

    private function removeDirectory(string $directory): void
    {
        /** @var string[] $files */
        $files = glob($directory.'/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($directory);
    }
}
