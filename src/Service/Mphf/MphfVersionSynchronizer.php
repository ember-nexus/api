<?php

declare(strict_types=1);

namespace App\Service\Mphf;

use App\Contract\MphfInterface;
use App\Factory\Type\RedisKeyFactory;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Safe\Exceptions\FilesystemException;
use Symfony\Contracts\Service\ResetInterface;
use Throwable;

use function Safe\fclose;
use function Safe\flock;
use function Safe\fopen;
use function Safe\unlink;

/**
 * Keeps the loaded `.mphf` table in sync with the build named in Redis (`mphf-version`).
 *
 * Runs on every API request and every CLI command: one Redis GET. A new version is loaded right away if its file is
 * already in the local directory. Otherwise the file is downloaded from the system bucket: after the response was sent
 * on HTTP requests (the current request carries on with the old table or the fallback), synchronously in CLI
 * processes. A non blocking flock makes sure that only one thread/process of a container downloads at a time.
 */
class MphfVersionSynchronizer implements ResetInterface
{
    private const int RECHECK_INTERVAL_IN_SECONDS = 10;

    private bool $synchronized = false;
    private ?string $currentVersion = null;
    private ?string $pendingDownload = null;
    private float $lastCheck = 0.0;

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyFactory,
        private MphfInterface $mphf,
        private MphfStorageService $mphfStorageService,
        private SystemBucketService $systemBucketService,
        private LoggerInterface $logger,
        private bool $mphfEnabled,
        private bool $mphfMadviseRandom,
        private int $mphfCliDownloadTimeout,
    ) {
    }

    /**
     * Compares Redis with the loaded version and loads the new file if it is available locally. Downloads are only
     * done right away if $downloadNow is true (CLI), otherwise they are remembered for {@see downloadPending()}.
     */
    public function synchronize(bool $downloadNow = false): void
    {
        $this->synchronized = true;
        $this->lastCheck = microtime(true);
        if (!$this->mphfEnabled || !$this->mphf->isAvailable()) {
            return;
        }
        try {
            $redisVersion = $this->redisClient->get((string) $this->redisKeyFactory->getMphfVersionRedisKey());
        } catch (Throwable $throwable) {
            $this->logger->warning('Unable to read the MPHF version from Redis, keeping the current state.', ['exception' => $throwable]);

            return;
        }
        $this->currentVersion = is_string($redisVersion) ? $redisVersion : '';

        if ('' === $this->currentVersion) {
            if ('' !== $this->mphf->version()) {
                $this->mphf->unload();
            }

            return;
        }
        if ($this->currentVersion === $this->mphf->version()) {
            return;
        }
        if (!$this->mphfStorageService->isValidVersion($this->currentVersion)) {
            $this->logger->error(sprintf("Redis holds the invalid MPHF version '%s', ignoring it.", $this->currentVersion));

            return;
        }
        if ($this->mphfStorageService->hasFile($this->currentVersion)) {
            $this->load($this->currentVersion);

            return;
        }
        $this->pendingDownload = $this->currentVersion;
        if ($downloadNow) {
            $this->downloadPending(true);
        }
    }

    /**
     * Synchronizes once per request/command; later calls are free. Long running commands call
     * {@see synchronizeIfStale()} instead.
     */
    public function ensureSynchronized(): void
    {
        if (!$this->synchronized) {
            $this->synchronize();
        }
    }

    public function synchronizeIfStale(bool $downloadNow = true): void
    {
        if (microtime(true) - $this->lastCheck >= self::RECHECK_INTERVAL_IN_SECONDS) {
            $this->synchronize($downloadNow);
        }
    }

    /**
     * True if the table in memory is the build which Redis names as current. Only then a typed query without result
     * proves that an element doesn't exist (see ElementTypeService).
     */
    public function isLoadedVersionCurrent(): bool
    {
        $this->ensureSynchronized();

        return null !== $this->currentVersion && '' !== $this->currentVersion && $this->currentVersion === $this->mphf->version();
    }

    /**
     * Version named in Redis, null if it wasn't read (yet), empty if there is none.
     */
    public function getCurrentVersion(): ?string
    {
        return $this->currentVersion;
    }

    public function hasPendingDownload(): bool
    {
        return null !== $this->pendingDownload;
    }

    /**
     * Downloads, verifies and loads the version which wasn't available locally. Does nothing if another process is
     * already downloading (HTTP), or waits for it up to the CLI timeout.
     */
    public function downloadPending(bool $blocking = false): void
    {
        $version = $this->pendingDownload;
        $this->pendingDownload = null;
        if (null === $version) {
            return;
        }
        try {
            $this->mphfStorageService->ensureDirectory();
            $lockHandle = fopen($this->mphfStorageService->getLockFilePath(), 'c');
        } catch (Throwable $throwable) {
            $this->logger->warning('Unable to prepare the local MPHF directory.', ['exception' => $throwable]);

            return;
        }
        try {
            if (!$this->acquireDownloadLock($lockHandle, $blocking)) {
                return;
            }
            // the file might have been downloaded by whoever held the lock before us
            if (!$this->mphfStorageService->hasFile($version)) {
                $this->download($version);
            }
            if ($this->mphfStorageService->hasFile($version)) {
                $this->load($version);
            }
        } catch (Throwable $throwable) {
            $this->logger->warning(sprintf("Unable to download MPHF build '%s'.", $version), ['exception' => $throwable]);
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }

    /**
     * @param resource $lockHandle
     */
    private function acquireDownloadLock(mixed $lockHandle, bool $blocking): bool
    {
        $deadline = microtime(true) + (float) ($blocking ? $this->mphfCliDownloadTimeout : 0);
        do {
            try {
                flock($lockHandle, LOCK_EX | LOCK_NB);

                return true;
            } catch (FilesystemException) {
                // somebody else holds the lock
            }
            if ($blocking) {
                usleep(200_000);
            }
        } while ($blocking && microtime(true) < $deadline);

        return false;
    }

    private function download(string $version): void
    {
        $temporaryPath = $this->mphfStorageService->getFilePath($version).'.tmp';
        try {
            $this->systemBucketService->download($this->mphfStorageService->getBucketKey($version), $temporaryPath);
            if (!$this->mphfStorageService->hasValidChecksum($temporaryPath)) {
                throw new RuntimeException('The checksum of the downloaded file does not match.');
            }
            $this->mphfStorageService->publish($temporaryPath, $version);
        } catch (Throwable $throwable) {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
            throw $throwable;
        }
    }

    private function load(string $version): void
    {
        $path = $this->mphfStorageService->getFilePath($version);
        $previous = $this->mphf->version();
        if (!$this->mphf->load($path, $version, $this->mphfMadviseRandom)) {
            $this->logger->error(sprintf("Unable to load MPHF build '%s': %s", $version, $this->mphf->lastError()));
            // a local file which fails its own checksum was damaged after it was published (e.g. truncated); it is
            // removed and fetched again. A file with a valid checksum which still can't be loaded is kept, so a build
            // which is bad on the bucket as well does not cause endless downloads.
            if (!$this->mphfStorageService->hasValidChecksum($path)) {
                $this->mphfStorageService->deleteFile($version);
                $this->pendingDownload = $version;
            }

            return;
        }
        $this->logger->info(sprintf("Loaded MPHF build '%s'.", $version));
        $this->mphfStorageService->cleanup(...array_filter([$version, $previous]));
    }

    public function reset(): void
    {
        $this->synchronized = false;
        $this->currentVersion = null;
        $this->pendingDownload = null;
        $this->lastCheck = 0.0;
    }
}
