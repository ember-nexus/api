<?php

declare(strict_types=1);

namespace App\Command;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Style\EmberNexusStyle;
use AsyncAws\S3\S3Client;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Laudis\Neo4j\Databags\Statement;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Predis\Client as RedisClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\OutputStyle;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Syndesi\ElasticEntityManager\Type\EntityManager as ElasticEntityManager;
use Syndesi\MongoEntityManager\Type\EntityManager as MongoEntityManager;

/**
 * @psalm-suppress PropertyNotSetInConstructor $io
 *
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 * @SuppressWarnings("PHPMD.NPathComplexity")
 */
#[AsCommand(name: 'healthcheck', description: 'Check the health of the API and required databases.')]
class HealthcheckCommand extends Command
{
    private OutputStyle $io;

    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private MongoEntityManager $mongoEntityManager,
        private ElasticEntityManager $elasticEntityManager,
        private RedisClient $redisClient,
        private AMQPStreamConnection $AMQPStreamConnection,
        private S3Client $s3Client,
        private EmberNexusConfiguration $emberNexusConfiguration,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
        parent::__construct();
    }

    private function getCypherVersion(): string
    {
        $cypherInfo = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                'CALL dbms.components() YIELD versions, edition UNWIND versions AS version RETURN version, edition;'
            )
        );
        $rawVersion = $cypherInfo->first()->get('version');
        if (!is_string($rawVersion)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property version as string, not %s.', get_debug_type($rawVersion))); // @codeCoverageIgnore
        }
        $rawEdition = $cypherInfo->first()->get('edition');
        if (!is_string($rawEdition)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property edition as string, not %s.', get_debug_type($rawEdition))); // @codeCoverageIgnore
        }

        return sprintf(
            '%s (%s)',
            $rawVersion,
            $rawEdition
        );
    }

    private function getMongoVersion(): string
    {
        $mongoClient = $this->mongoEntityManager->getClient();
        $mongoDatabase = $this->mongoEntityManager->getDatabase();
        if (null === $mongoDatabase) {
            throw new Exception('Mongo database name can not be null.');
        }
        $mongo = $mongoClient->selectDatabase($mongoDatabase)->command(['buildInfo' => 1]);

        return $mongo->toArray()[0]->getArrayCopy()['version'];
    }

    private function getElasticVersion(): string
    {
        $elasticClient = $this->elasticEntityManager->getClient();
        $elastic = $elasticClient->sendRequest(new Request('GET', '/'));

        /**
         * @psalm-suppress PossiblyUndefinedMethod
         *
         * @phpstan-ignore-next-line
         */
        return $elastic->asArray()['version']['number'];
    }

    private function getRedisVersion(): string
    {
        return $this->redisClient->info()['Server']['redis_version'];
    }

    private function getRabbitMqVersion(): string
    {
        return $this->AMQPStreamConnection->getServerProperties()['version'][1];
    }

    private function getS3Status(): string
    {
        $isStorageBucketOnline = $this->s3Client->bucketExists(['Bucket' => $this->emberNexusConfiguration->getFileS3StorageBucket()])->isSuccess();
        if (!$isStorageBucketOnline) {
            throw new Exception(sprintf('Unable to connect to bucket %s.', $this->emberNexusConfiguration->getFileS3StorageBucket()));
        }
        $isUploadBucketOnline = $this->s3Client->bucketExists(['Bucket' => $this->emberNexusConfiguration->getFileS3UploadBucket()])->isSuccess();
        if (!$isUploadBucketOnline) {
            throw new Exception(sprintf('Unable to connect to bucket %s.', $this->emberNexusConfiguration->getFileS3UploadBucket()));
        }

        return 'online';
    }

    private function getAlpineVersion(): string
    {
        $alpineVersion = \Safe\shell_exec('cat /etc/os-release | grep -i version') ?? '=unknown';

        /**
         * @psalm-suppress PossiblyUndefinedArrayOffset
         */
        return trim(explode('=', $alpineVersion, 2)[1]);
    }

    private function getCaddyVersion(): string
    {
        $caddyVersion = 'unknown';
        $caddyVersionOutput = explode(' ', \Safe\shell_exec('frankenphp -v 2>&1') ?? '');
        if (count($caddyVersionOutput) > 1) {
            $caddyVersion = \Safe\preg_replace('/[^0-9.]/', '', $caddyVersionOutput[0]);
        }

        /** @var string $caddyVersion */
        return $caddyVersion;
    }

    private function getFrankenPhpVersion(): string
    {
        $frankenPhpVersion = 'unknown';
        $frankenPhpVersionOutput = getenv('FRANKENPHP_VERSION');
        if (is_string($frankenPhpVersionOutput)) {
            $frankenPhpVersion = \Safe\preg_replace('/[^0-9.]/', '', $frankenPhpVersionOutput);
        }

        /** @var string $frankenPhpVersion */
        return $frankenPhpVersion;
    }

    /**
     * @psalm-suppress InvalidFalsableReturnType
     * @psalm-suppress FalsableReturnStatement
     */
    private function getPhpVersion(): string
    {
        return phpversion();
    }

    /**
     * @psalm-suppress PossiblyUndefinedArrayOffset
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Healthcheck');

        $this->io->startSection('Check if databases are online');
        $this->io->writeln(sprintf('Neo4j version:         %s', $this->getCypherVersion()));
        $this->io->writeln(sprintf('MongoDB version:       %s', $this->getMongoVersion()));
        $this->io->writeln(sprintf('Elasticsearch version: %s', $this->getElasticVersion()));
        $this->io->writeln(sprintf('Redis version:         %s', $this->getRedisVersion()));
        $this->io->writeln(sprintf('RabbitMQ version:      %s', $this->getRabbitMqVersion()));
        $this->io->writeln(sprintf('S3 buckets:            %s', $this->getS3Status()));
        $this->io->stopSection('All databases are online.');

        $this->io->startSection('Check internal services');
        $this->io->writeln(sprintf('Alpine version:        %s', $this->getAlpineVersion()));
        $this->io->writeln(sprintf('Caddy version:         %s', $this->getCaddyVersion()));
        $this->io->writeln(sprintf('FrankenPHP version:    %s', $this->getFrankenPhpVersion()));
        $this->io->writeln(sprintf('PHP version:           %s', $this->getPhpVersion()));
        $this->io->stopSection('Internal services are ok.');

        $this->io->startSection('Check API');

        $client = new Client();
        $res = $client->request('GET', 'http://localhost');
        if (200 !== $res->getStatusCode() && 429 !== $res->getStatusCode()) {
            throw new Exception(sprintf('Get index endpoint is not online, expected status code 200 or 429, got %s.', $res->getStatusCode()));
        }
        $this->io->writeln('Get index endpoint is online.');

        $this->io->stopSection('API is ok.');

        $this->io->finalMessage('Status is ok.');

        return Command::SUCCESS;
    }
}
