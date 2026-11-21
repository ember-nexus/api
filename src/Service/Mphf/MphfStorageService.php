<?php

declare(strict_types=1);

namespace App\Service\Mphf;

use function Safe\chmod;
use function Safe\fclose;
use function Safe\filesize;
use function Safe\fopen;
use function Safe\fread;
use function Safe\glob;
use function Safe\mkdir;
use function Safe\preg_match;
use function Safe\rename;
use function Safe\unlink;

/**
 * Local directory of the `.mphf` files, shared by all processes (web server, cron, CLI) of a container, so they all
 * map one physical copy: tmpfs (`memory`, counts against the container's memory limit) or a local disk (`disk`, page
 * cache, can be evicted).
 */
class MphfStorageService
{
    public const string BUCKET_PREFIX = 'mphf/';
    private const string VERSION_PATTERN = '/^\d{8}-\d{4}\.mphf$/';
    private const int MAX_PREFACE_SIZE = 1024;
    private const int ALIGNMENT = 64;

    public function __construct(
        private string $mphfStorage,
        private string $mphfDir,
    ) {
    }

    public function getDirectory(): string
    {
        if ('' !== $this->mphfDir) {
            return rtrim($this->mphfDir, '/');
        }

        return 'disk' === $this->mphfStorage ? sys_get_temp_dir().'/ember-nexus-mphf' : '/dev/shm/ember-nexus-mphf';
    }

    /**
     * The version is read from Redis and ends up in file paths and bucket keys, so only the exact build file name
     * pattern (`yyyymmdd-hhii.mphf`) is accepted.
     */
    public function isValidVersion(string $version): bool
    {
        return 1 === preg_match(self::VERSION_PATTERN, $version);
    }

    public function getFilePath(string $version): string
    {
        return $this->getDirectory().'/'.$version;
    }

    public function getLockFilePath(): string
    {
        return $this->getDirectory().'/.lock';
    }

    public function getBucketKey(string $version): string
    {
        return self::BUCKET_PREFIX.$version;
    }

    public function hasFile(string $version): bool
    {
        return $this->isValidVersion($version) && is_file($this->getFilePath($version));
    }

    public function ensureDirectory(): void
    {
        $directory = $this->getDirectory();
        if (!is_dir($directory)) {
            mkdir($directory, 0o775, true);
        }
    }

    /**
     * Checks a file against its preface (see lib/UuidToTypeMphfLookup/README.md, including the padding rules): the
     * content, which starts after the first blank line at a multiple of 64 bytes, must have exactly the stated length
     * and the stated SHA-256. Streams the file, so it needs no memory.
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    public function hasValidChecksum(string $path): bool
    {
        $size = filesize($path);
        if ($size < 5) {
            return false;
        }
        $handle = fopen($path, 'rb');
        try {
            $head = fread($handle, min(self::MAX_PREFACE_SIZE, $size));
            $end = strpos($head, "\n\n");
            if (false === $end) {
                return false;
            }
            $lines = explode("\n", substr($head, 0, $end));
            if ('MPHF' !== array_shift($lines)) {
                return false;
            }
            $length = null;
            $hash = null;
            foreach ($lines as $line) {
                $parts = explode(': ', $line, 2);
                if (2 !== count($parts)) {
                    return false;
                }
                $value = rtrim($parts[1], ' ');
                // padding rules of the preface: only the hash line may end with spaces, fewer than 64 of them
                $padding = strlen($parts[1]) - strlen($value);
                if ($padding > 0 && ('sha256' !== $parts[0] || $padding >= self::ALIGNMENT)) {
                    return false;
                }
                if ('content length' === $parts[0] && 1 === preg_match('/^\d{1,18}$/', $value)) {
                    $length = (int) $value;
                } elseif ('sha256' === $parts[0] && 1 === preg_match('/^[0-9a-f]{64}$/', $value)) {
                    $hash = $value;
                }
            }
            $contentOffset = $end + 2;
            if (0 !== $contentOffset % self::ALIGNMENT) {
                return false;
            }
            if (null === $length || null === $hash || $size - $contentOffset !== $length) {
                return false;
            }
            if (0 !== fseek($handle, $contentOffset)) {
                return false;
            }
            $context = hash_init('sha256');
            $remaining = $length;
            while ($remaining > 0) {
                $chunk = fread($handle, min(1 << 20, $remaining));
                hash_update($context, $chunk);
                $remaining -= strlen($chunk);
            }
        } finally {
            fclose($handle);
        }

        return hash_equals($hash, hash_final($context));
    }

    /**
     * Moves a verified download to its final place; the rename is atomic, so readers never see partial files.
     */
    public function publish(string $temporaryPath, string $version): void
    {
        // read-only, so nothing in the container writes into a file which processes have mapped
        chmod($temporaryPath, 0o444);
        rename($temporaryPath, $this->getFilePath($version));
    }

    /**
     * Removes a local file (unlinking a file which processes have mapped is safe).
     */
    public function deleteFile(string $version): void
    {
        if ($this->hasFile($version)) {
            unlink($this->getFilePath($version));
        }
    }

    /**
     * Deletes local `.mphf` files other than the given ones (unlinking a mapped file is safe). Leftover `.tmp` files of
     * crashed downloads are removed too.
     */
    public function cleanup(string ...$keepVersions): void
    {
        /** @var string[] $files */
        $files = glob($this->getDirectory().'/*');
        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $keepVersions, true)) {
                continue;
            }
            if ($this->isValidVersion($name) || str_ends_with($name, '.tmp')) {
                unlink($file);
            }
        }
    }
}
