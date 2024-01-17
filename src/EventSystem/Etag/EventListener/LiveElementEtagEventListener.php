<?php

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ElementEtagEvent;
use App\Helper\RedisKeyHelper;
use App\Type\EtagCalculator;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\DateTimeZoneId;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class LiveElementEtagEventListener
{
    public const int REDIS_ELEMENT_TTL_IN_SECONDS = 3600;

    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private CypherEntityManager $cypherEntityManager,
        private RedisClient $redisClient,
        private LoggerInterface $logger
    ) {
    }

    public function onElementEtagEvent(ElementEtagEvent $event): void
    {
        $this->logger->debug(
            'Calculating Etag for element.',
            [
                'elementUuid' => $event->getElementUuid()->toString(),
            ]
        );
        $etag = $this->getElementEtag($event->getElementUuid());
        $this->logger->debug(
            'Calculated Etag for element.',
            [
                'elementUuid' => $event->getElementUuid()->toString(),
                'etag' => $etag,
            ]
        );

        $redisKey = RedisKeyHelper::getEtagElementRedisKey($event->getElementUuid());

        $this->logger->debug(
            'Trying to persist Etag for element in Redis.',
            [
                'elementUuid' => $event->getElementUuid()->toString(),
                'redisKey' => $redisKey,
                'etag' => $etag,
            ]
        );

        $redisValue = $etag;
        $this->redisClient->set($redisKey, $redisValue, 'EX', self::REDIS_ELEMENT_TTL_IN_SECONDS);

        $event->setEtag($etag);
        $event->stopPropagation();
    }

    private function getElementEtag(UuidInterface $elementUuid): string
    {
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                "OPTIONAL MATCH (node {id: \$elementUuid})\n".
                "OPTIONAL MATCH ()-[relation {id: \$elementUuid}]->()\n".
                'RETURN node.updated, relation.updated',
                [
                    'elementUuid' => $elementUuid->toString(),
                ]
            )
        );
        $updated = $result[0]['node.updated'] ?? $result[0]['relation.updated'] ?? null;
        if (null === $updated) {
            throw new Exception(sprintf('Unable to find node or relation with id %s.', $elementUuid->toString()));
        }
        if (!($updated instanceof DateTimeZoneId)) {
            throw new Exception(sprintf('Expected variable element.updated to be of type %s, got %s.', DateTimeZoneId::class, get_class($updated)));
        }

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        $etagCalculator->addUuid($elementUuid);
        $etagCalculator->addDateTime($updated->toDateTime());

        return $etagCalculator->getEtag();
    }
}
