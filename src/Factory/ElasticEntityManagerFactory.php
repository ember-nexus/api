<?php

namespace App\Factory;

use Elastic\Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Syndesi\ElasticEntityManager\Type\EntityManager;

class ElasticEntityManagerFactory
{
    public function __construct(private EventDispatcherInterface $eventDispatcher, private LoggerInterface $logger)
    {
    }

    public function createElasticEntityManager(): EntityManager
    {
        $client = ClientBuilder::create()
            ->setHosts(['neo4j-php-elasticsearch:9200'])
            ->build();

        return new EntityManager($client, $this->eventDispatcher, $this->logger);
    }
}
