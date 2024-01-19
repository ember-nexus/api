<?php

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ChildrenCollectionEtagEvent;
use App\Factory\Type\RedisKeyFactory;
use App\Type\EtagCalculator;
use App\Type\RedisValueType;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\DateTimeZoneId;
use Predis\Client as RedisClient;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class LiveChildrenCollectionEtagEventListener
{
    public const int REDIS_CHILDREN_COLLECTION_TTL_IN_SECONDS = 3600;

    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private CypherEntityManager $cypherEntityManager,
        private RedisClient $redisClient,
        private RedisKeyFactory $redisKeyTypeFactory,
        private LoggerInterface $logger
    ) {
    }

    public function onChildrenCollectionEtagEvent(ChildrenCollectionEtagEvent $event): void
    {
        $this->logger->debug(
            'Calculating Etag for children collection.',
            [
                'parentUuid' => $event->getParentUuid()->toString(),
            ]
        );
        $etag = $this->getChildrenCollectionEtag($event->getParentUuid());
        $this->logger->debug(
            'Calculated Etag for children collection.',
            [
                'parentUuid' => $event->getParentUuid()->toString(),
                'etag' => $etag,
            ]
        );

        $redisKey = $this->redisKeyTypeFactory->getEtagChildrenCollectionRedisKey($event->getParentUuid());

        $this->logger->debug(
            'Trying to persist Etag for children collection in Redis.',
            [
                'parentUuid' => $event->getParentUuid()->toString(),
                'redisKey' => $redisKey,
                'etag' => $etag,
            ]
        );

        $redisValue = $etag;
        if (null === $redisValue) {
            $redisValue = RedisValueType::NULL->value;
        }
        $this->redisClient->set($redisKey, $redisValue, 'EX', self::REDIS_CHILDREN_COLLECTION_TTL_IN_SECONDS);

        $event->setEtag($etag);
        $event->stopPropagation();
    }

    private function getChildrenCollectionEtag(UuidInterface $parentUuid): ?string
    {
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        // todo: add relation id + updated to calculation
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH ({id: \$parentUuid})-[:OWNS]->(children)\n".
                    "WITH children\n".
                    "LIMIT %d\n".
                    "WITH children ORDER BY children.id\n".
                    "WITH [children.id, children.updated] AS childrenList\n".
                    'RETURN collect(childrenList) AS childrenList',
                    $limit + 1
                ),
                [
                    'parentUuid' => $parentUuid->toString(),
                ]
            )
        );
        if (1 !== count($result)) {
            throw new Exception('Unexpected result');
        }
        if (count($result[0]['childrenList']) > $limit) {
            return null;
        }

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        foreach ($result[0]['childrenList'] as $childIdUpdatedPair) {
            $childId = Uuid::fromString($childIdUpdatedPair[0]);
            $childUpdated = $childIdUpdatedPair[1];
            if (!($childUpdated instanceof DateTimeZoneId)) {
                throw new Exception(sprintf('Expected variable element.updated to be of type %s, got %s.', DateTimeZoneId::class, get_class($childUpdated)));
            }
            $etagCalculator->addUuid($childId);
            $etagCalculator->addDateTime($childUpdated->toDateTime());
        }

        return $etagCalculator->getEtag();
    }
}
