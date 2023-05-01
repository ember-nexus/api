<?php

namespace App\Factory;

use Elastic\Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Syndesi\ElasticEntityManager\Type\EntityManager;

class ElasticEntityManagerFactory
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        private string $elasticAuth
    )
    {
    }

    public function createElasticEntityManager(): EntityManager
    {
        $client = ClientBuilder::create()
            ->setHosts([$this->elasticAuth])
            ->build();

        return new EntityManager($client, $this->eventDispatcher, $this->logger);
    }
}
