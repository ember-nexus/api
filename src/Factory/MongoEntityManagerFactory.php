<?php

namespace App\Factory;

use MongoDB\Client;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Syndesi\MongoEntityManager\Type\EntityManager;

class MongoEntityManagerFactory
{
    public function __construct(private EventDispatcherInterface $eventDispatcher, private LoggerInterface $logger)
    {
    }

    public function createMongoEntityManager(): EntityManager
    {
        $client = new Client('mongodb://mongodb:password@neo4j-php-mongodb:27017');

        return new EntityManager('tion', $client, $this->eventDispatcher, $this->logger);
    }
}
