<?php

declare(strict_types=1);

namespace App\Tests\FeatureTests\Command;

use App\Tests\FeatureTests\BaseRequestTestCase;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Predis\Client as RedisClient;

/**
 * Shared helpers of the tests of the cron commands. The cron commands work on all uploads and queue messages of the
 * instance, therefore the tests are part of the group `command`, which is executed after the parallel tests.
 */
abstract class BaseCronTestCase extends BaseRequestTestCase
{
    protected const string TOKEN = 'secret-token:1nc1pFdBO2QLYRMMvULgtQ';
    protected const int CHUNK_SIZE = 5 * 1024 * 1024;

    protected function getRedisClient(): RedisClient
    {
        return new RedisClient($_ENV['REDIS_AUTH']);
    }

    /**
     * @param array<string, string> $environment
     *
     * @return array{0: int, 1: string} exit code and output
     */
    protected function runConsoleCommand(string $command, array $environment = []): array
    {
        // this spawns a subprocess without a tty, so under the tty-bypass rule a container-wide `DISABLE_CRON=true`
        // would otherwise make these tests no-op; default it to disabled here unless a test explicitly wants to
        // exercise the disabled path itself
        $environment = ['DISABLE_CRON' => '0', ...$environment];

        $prefix = '';
        foreach ($environment as $name => $value) {
            $prefix .= sprintf('%s=%s ', $name, escapeshellarg($value));
        }
        $output = [];
        $exitCode = 0;
        \Safe\exec(sprintf('%sphp bin/console %s 2>&1', $prefix, $command), $output, $exitCode);

        return [$exitCode, implode("\n", $output)];
    }

    protected function createNode(string $name): string
    {
        return $this->getUuidFromLocation($this->runPostRequest('/', self::TOKEN, [
            'type' => 'Data',
            'data' => ['name' => $name],
        ]));
    }

    /**
     * Starts an unfinished upload with a single chunk, i.e. one chunk object exists in the upload bucket.
     */
    protected function createUpload(string $elementId): string
    {
        $response = $this->runUploadRequest(
            'POST',
            sprintf('/%s/file', $elementId),
            str_repeat('a', self::CHUNK_SIZE),
            self::TOKEN,
            [
                'Upload-Complete' => '?0',
                'Content-Type' => 'application/octet-stream',
            ]
        );
        $this->assertNoContentResponse($response, true);

        return $this->getUuidFromLocation($response);
    }

    protected function setUploadExpiration(string $uploadId, string $expirationExpression): void
    {
        $result = $this->getCypherClient()->run(
            sprintf('MATCH (u:Upload {id: $id}) SET u.expires = %s RETURN count(u) AS count', $expirationExpression),
            ['id' => $uploadId]
        );
        $this->assertSame(1, $result->first()->get('count'));
    }

    protected function setUploadProperty(string $uploadId, string $propertyExpression): void
    {
        $result = $this->getCypherClient()->run(
            sprintf('MATCH (u:Upload {id: $id}) SET %s RETURN count(u) AS count', $propertyExpression),
            ['id' => $uploadId]
        );
        $this->assertSame(1, $result->first()->get('count'));
    }

    protected function uploadNodeExists(string $uploadId): bool
    {
        $result = $this->getCypherClient()->run('MATCH (u:Upload {id: $id}) RETURN count(u) AS count', ['id' => $uploadId]);

        return 1 === $result->first()->get('count');
    }

    protected function deleteNode(string $elementId): void
    {
        $this->assertIsDeletedResponse($this->runDeleteRequest(sprintf('/%s', $elementId), self::TOKEN));
    }

    protected function getRabbitMqConnection(): AMQPStreamConnection
    {
        $parts = \Safe\parse_url($_ENV['RABBITMQ_AUTH']);

        return new AMQPStreamConnection($parts['host'], $parts['port'] ?? 5672, $parts['user'], $parts['pass']);
    }
}
