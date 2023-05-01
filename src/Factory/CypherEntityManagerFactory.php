<?php

namespace App\Factory;

use Laudis\Neo4j\ClientBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Syndesi\CypherEntityManager\Type\EntityManager;

class CypherEntityManagerFactory
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        private string $cypherAuth
    )
    {
    }

    public function createCypherEntityManager(): EntityManager
    {
        $client = ClientBuilder::create()
            ->withDriver('default', $this->cypherAuth)
            ->build();

        return new EntityManager($client, $this->eventDispatcher, $this->logger);
    }
}
