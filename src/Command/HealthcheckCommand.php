<?php

declare(strict_types=1);

namespace App\Command;

use App\Style\EmberNexusStyle;
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
        private AMQPStreamConnection $AMQPStreamConnection
    ) {
        parent::__construct();
    }

    /**
     * @psalm-suppress PossiblyUndefinedArrayOffset
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new EmberNexusStyle($input, $output);

        $this->io->title('Healthcheck');

        $this->io->startSection('Check if databases are online');

        $neo4jInfo = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                'CALL dbms.components() YIELD versions, edition UNWIND versions AS version RETURN version, edition;'
            )
        );
        $neo4jVersion = sprintf(
            '%s (%s)',
            $neo4jInfo->first()->get('version'),
            $neo4jInfo->first()->get('edition')
        );

        $this->io->writeln(sprintf(
            'Neo4j version:         %s',
            $neo4jVersion
        ));

        $mongoClient = $this->mongoEntityManager->getClient();
        $mongoDatabase = $this->mongoEntityManager->getDatabase();
        if (null === $mongoDatabase) {
            throw new Exception('Mongo database name can not be null.');
        }
        $mongo = $mongoClient->selectDatabase($mongoDatabase)->command(['buildInfo' => 1]);
        $mongoVersion = $mongo->toArray()[0]->getArrayCopy()['version'];

        $this->io->writeln(sprintf(
            'MongoDB version:       %s',
            $mongoVersion
        ));

        $elasticClient = $this->elasticEntityManager->getClient();
        $elastic = $elasticClient->sendRequest(new Request('GET', '/'));
        /**
         * @psalm-suppress PossiblyUndefinedMethod
         *
         * @phpstan-ignore-next-line
         */
        $elasticVersion = $elastic->asArray()['version']['number'];

        $this->io->writeln(sprintf(
            'Elasticsearch version: %s',
            $elasticVersion
        ));

        $redisVersion = $this->redisClient->info()['Server']['redis_version'];

        $this->io->writeln(sprintf(
            'Redis version:         %s',
            $redisVersion
        ));

        $rabbitMqVersion = $this->AMQPStreamConnection->getServerProperties()['version'][1];

        $this->io->writeln(sprintf(
            'RabbitMQ version:      %s',
            $rabbitMqVersion
        ));

        $this->io->stopSection('All databases are online.');

        $this->io->startSection('Check internal services');

        $alpineVersion = \Safe\shell_exec('cat /etc/os-release | grep -i version');
        $alpineVersion = trim(explode('=', $alpineVersion, 2)[1]);

        $this->io->writeln(sprintf(
            'Alpine version:        %s',
            $alpineVersion
        ));

        $nginxUnitVersion = \Safe\shell_exec('unitd --version 2>&1');
        $nginxUnitVersion = explode("\n", $nginxUnitVersion, 2)[0];
        $nginxUnitVersion = explode(': ', $nginxUnitVersion, 2)[1];

        $this->io->writeln(sprintf(
            'NGINX Unit version:    %s',
            $nginxUnitVersion
        ));

        $this->io->writeln(sprintf(
            'PHP version:           %s',
            phpversion()
        ));

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
