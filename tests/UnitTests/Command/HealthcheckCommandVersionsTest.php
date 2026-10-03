<?php

declare(strict_types=1);

namespace App\Tests\UnitTests\Command;

use App\Command\HealthcheckCommand;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use AsyncAws\S3\S3Client;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Contracts\ClientInterface as CypherClientInterface;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Databags\SummarizedResult;
use Laudis\Neo4j\Types\CypherMap;
use MongoDB\Client as MongoClient;
use MongoDB\Database as MongoDatabase;
use MongoDB\Driver\CursorInterface;
use MongoDB\Model\BSONDocument;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Predis\Client as RedisClient;
use Prophecy\PhpUnit\ProphecyTrait;
use ReflectionMethod;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

/**
 * The Cypher/Mongo/Redis/RabbitMQ parts of the healthcheck; S3 has its own test (HealthcheckCommandS3Test). Elastic
 * (its client is a final SDK class with a final transport, only reachable through the real HTTP layer), the version
 * probes (Alpine/Caddy/FrankenPHP/PHP) and the final API ping are covered by the command example test instead
 * (SystemHealthcheckTest), which runs against real services.
 */
#[Small]
#[CoversClass(HealthcheckCommand::class)]
class HealthcheckCommandVersionsTest extends TestCase
{
    use ProphecyTrait;

    private function buildCommand(
        ?CypherEntityManager $cypherEntityManager = null,
        ?MongoEntityManager $mongoEntityManager = null,
        ?RedisClient $redisClient = null,
        ?AMQPStreamConnection $AMQPStreamConnection = null,
    ): HealthcheckCommand {
        return new HealthcheckCommand(
            $cypherEntityManager ?? $this->prophesize(CypherEntityManager::class)->reveal(),
            $mongoEntityManager ?? $this->prophesize(MongoEntityManager::class)->reveal(),
            $this->prophesize(ElasticEntityManager::class)->reveal(),
            $redisClient ?? $this->prophesize(RedisClient::class)->reveal(),
            $AMQPStreamConnection ?? $this->prophesize(AMQPStreamConnection::class)->reveal(),
            $this->prophesize(S3Client::class)->reveal(),
            $this->prophesize(EmberNexusConfiguration::class)->reveal(),
            $this->prophesize(Server500LogicErrorExceptionFactory::class)->reveal(),
        );
    }

    private function invoke(HealthcheckCommand $command, string $method): string
    {
        /** @var string */
        return (new ReflectionMethod($command, $method))->invoke($command);
    }

    public function testGetCypherVersion(): void
    {
        $summary = null;
        $result = new SummarizedResult($summary, [new CypherMap(['version' => '5.25.0', 'edition' => 'community'])]);

        $cypherClient = $this->prophesize(CypherClientInterface::class);
        $cypherClient->runStatement(Statement::create(
            'CALL dbms.components() YIELD versions, edition UNWIND versions AS version RETURN version, edition;'
        ))->willReturn($result);

        $cypherEntityManager = $this->prophesize(CypherEntityManager::class);
        $cypherEntityManager->getClient()->willReturn($cypherClient->reveal());

        $command = $this->buildCommand(cypherEntityManager: $cypherEntityManager->reveal());

        $this->assertSame('5.25.0 (community)', $this->invoke($command, 'getCypherVersion'));
    }

    public function testGetMongoVersion(): void
    {
        $cursor = $this->prophesize(CursorInterface::class);
        $cursor->toArray()->willReturn([new BSONDocument(['version' => '8.0.1'])]);

        $mongoDatabase = $this->prophesize(MongoDatabase::class);
        $mongoDatabase->command(['buildInfo' => 1])->willReturn($cursor->reveal());

        $mongoClient = $this->prophesize(MongoClient::class);
        $mongoClient->selectDatabase('ember-nexus')->willReturn($mongoDatabase->reveal());

        $mongoEntityManager = $this->prophesize(MongoEntityManager::class);
        $mongoEntityManager->getClient()->willReturn($mongoClient->reveal());
        $mongoEntityManager->getDatabase()->willReturn('ember-nexus');

        $command = $this->buildCommand(mongoEntityManager: $mongoEntityManager->reveal());

        $this->assertSame('8.0.1', $this->invoke($command, 'getMongoVersion'));
    }

    public function testGetMongoVersionFailsWithoutDatabaseName(): void
    {
        $mongoEntityManager = $this->prophesize(MongoEntityManager::class);
        $mongoEntityManager->getClient()->willReturn($this->prophesize(MongoClient::class)->reveal());
        $mongoEntityManager->getDatabase()->willReturn(null);

        $command = $this->buildCommand(mongoEntityManager: $mongoEntityManager->reveal());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Mongo database name can not be null.');

        $this->invoke($command, 'getMongoVersion');
    }

    public function testGetRedisVersion(): void
    {
        $redisClient = $this->prophesize(RedisClient::class);
        $redisClient->info()->willReturn(['Server' => ['redis_version' => '8.0.2']]);

        $command = $this->buildCommand(redisClient: $redisClient->reveal());

        $this->assertSame('8.0.2', $this->invoke($command, 'getRedisVersion'));
    }

    public function testGetRabbitMqVersion(): void
    {
        $connection = $this->prophesize(AMQPStreamConnection::class);
        $connection->getServerProperties()->willReturn(['version' => ['S', '4.1.5']]);

        $command = $this->buildCommand(AMQPStreamConnection: $connection->reveal());

        $this->assertSame('4.1.5', $this->invoke($command, 'getRabbitMqVersion'));
    }
}
