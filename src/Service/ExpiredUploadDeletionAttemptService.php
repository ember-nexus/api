<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Type\RedisKeyFactory;
use DateTimeZone;
use Predis\Client as RedisClient;
use Safe\DateTimeImmutable;
use Safe\Exceptions\JsonException;

/**
 * Remembers failed attempts of `cron:delete-expired-uploads` per upload in Redis, so that a single broken upload is
 * neither retried on every cron run (log noise, wasted work) nor blocks the other uploads forever.
 *
 * Retry policy: after the first failure the upload is skipped for one hour, after the second one for 24 hours; the
 * third failure is final, see {@see MAX_ATTEMPTS}.
 */
class ExpiredUploadDeletionAttemptService
{
    public const int MAX_ATTEMPTS = 3;
    // longer than the last retry delay, so that the counter survives until the final attempt
    public const int TTL_IN_SECONDS = 7 * 24 * 60 * 60;
    /**
     * @var array<int, int> delay in seconds until the next attempt, indexed by the number of failed attempts
     */
    private const array RETRY_DELAYS_IN_SECONDS = [
        1 => 3600,
        2 => 86400,
    ];

    public function __construct(
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyFactory,
    ) {
    }

    /**
     * True if a previous attempt failed and its retry delay has not passed yet.
     */
    public function isDeferred(string $uploadId): bool
    {
        $notBefore = $this->read($uploadId)['notBefore'];

        return $notBefore > $this->now();
    }

    /**
     * @return int number of failed attempts including this one
     */
    public function recordFailure(string $uploadId): int
    {
        $attempts = $this->read($uploadId)['attempts'] + 1;
        $notBefore = $this->now() + (self::RETRY_DELAYS_IN_SECONDS[$attempts] ?? 0);
        $this->redisClient->set(
            (string) $this->redisKeyFactory->getCronDeleteExpiredUploadRedisKey($uploadId),
            \Safe\json_encode(['attempts' => $attempts, 'notBefore' => $notBefore]),
            'EX',
            self::TTL_IN_SECONDS
        );

        return $attempts;
    }

    public function clear(string $uploadId): void
    {
        $this->redisClient->del([(string) $this->redisKeyFactory->getCronDeleteExpiredUploadRedisKey($uploadId)]);
    }

    // time() returns a timezone-independent Unix timestamp already, but the PHP default timezone can still affect
    // date/time functions elsewhere; going through UTC explicitly here avoids relying on that default.
    private function now(): int
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->getTimestamp();
    }

    /**
     * @return array{attempts: int, notBefore: int}
     */
    private function read(string $uploadId): array
    {
        $value = $this->redisClient->get((string) $this->redisKeyFactory->getCronDeleteExpiredUploadRedisKey($uploadId));
        if (!is_string($value)) {
            return ['attempts' => 0, 'notBefore' => 0];
        }
        try {
            $data = \Safe\json_decode($value, true);
        } catch (JsonException) {
            return ['attempts' => 0, 'notBefore' => 0];
        }
        if (!is_array($data)) {
            return ['attempts' => 0, 'notBefore' => 0];
        }
        $attempts = $data['attempts'] ?? 0;
        $notBefore = $data['notBefore'] ?? 0;

        return [
            'attempts' => is_int($attempts) ? $attempts : 0,
            'notBefore' => is_int($notBefore) ? $notBefore : 0,
        ];
    }
}
