<?php

namespace App\Factory;

use Laudis\Neo4j\ClientBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Syndesi\CypherEntityManager\Type\EntityManager;

class CypherEntityManagerFactory
{
    public function __construct(private EventDispatcherInterface $eventDispatcher, private LoggerInterface $logger)
    {
    }

    public function createCypherEntityManager(EventDispatcherInterface $eventDispatcher, LoggerInterface $logger): EntityManager
    {
        $client = ClientBuilder::create()
            ->withDriver('bolt', 'bolt://neo4j:password@neo4j-php-neo4j')
            ->build();

        return new EntityManager($client, $this->eventDispatcher, $this->logger);
    }
}
