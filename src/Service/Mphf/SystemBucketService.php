<?php

declare(strict_types=1);

namespace App\Service\Mphf;

use AsyncAws\S3\S3Client;

use function Safe\fclose;
use function Safe\fopen;

/**
 * Access to the system bucket, which holds the `mphf/<yyyymmdd-hhii>.mphf` builds. It is separate from the buckets of
 * the user files and never mixed with them.
 *
 * @codeCoverageIgnore thin wrapper around the S3 client, whose results can't be mocked
 */
class SystemBucketService
{
    public function __construct(
        private S3Client $s3Client,
        private string $s3SystemBucket,
    ) {
    }

    public function getBucket(): string
    {
        return $this->s3SystemBucket;
    }

    /**
     * Creates the bucket if it doesn't exist yet, like the buckets of the files (which the dev setup creates up front).
     */
    public function ensureBucketExists(): void
    {
        if (!$this->s3Client->bucketExists(['Bucket' => $this->s3SystemBucket])->isSuccess()) {
            $this->s3Client->createBucket(['Bucket' => $this->s3SystemBucket])->resolve();
        }
    }

    public function exists(string $key): bool
    {
        return $this->s3Client->objectExists(['Bucket' => $this->s3SystemBucket, 'Key' => $key])->isSuccess();
    }

    /**
     * Uploads a local file; builds are immutable, so the caller has to check that the key is free.
     */
    public function upload(string $key, string $path): void
    {
        $handle = fopen($path, 'rb');
        try {
            $this->s3Client->putObject([
                'Bucket' => $this->s3SystemBucket,
                'Key' => $key,
                'Body' => $handle,
                'ContentType' => 'application/octet-stream',
            ])->resolve();
        } finally {
            fclose($handle);
        }
    }

    /**
     * Streams an object to a local file.
     */
    public function download(string $key, string $path): void
    {
        $body = $this->s3Client->getObject(['Bucket' => $this->s3SystemBucket, 'Key' => $key])->getBody();
        $target = fopen($path, 'wb');
        try {
            foreach ($body->getChunks() as $chunk) {
                \Safe\fwrite($target, $chunk);
            }
            \Safe\fflush($target);
        } finally {
            fclose($target);
        }
    }

    /**
     * @return string[] keys below the prefix, in S3's lexicographic order
     */
    public function list(string $prefix): array
    {
        $keys = [];
        foreach ($this->s3Client->listObjectsV2(['Bucket' => $this->s3SystemBucket, 'Prefix' => $prefix]) as $object) {
            if (!($object instanceof \AsyncAws\S3\ValueObject\AwsObject)) {
                continue;
            }
            $key = $object->getKey();
            if (null !== $key) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    public function delete(string $key): void
    {
        $this->s3Client->deleteObject(['Bucket' => $this->s3SystemBucket, 'Key' => $key])->resolve();
    }
}
