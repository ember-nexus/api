<?php

declare(strict_types=1);

namespace App\Factory;

use MongoDB\Client;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Syndesi\MongoEntityManager\Type\EntityManager;

class MongoEntityManagerFactory
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        private string $mongoAuth,
    ) {
    }

    public function createMongoEntityManager(): EntityManager
    {
        $client = new Client($this->mongoAuth);

        return new EntityManager('ember-nexus', $client, $this->eventDispatcher, $this->logger);
    }
}
