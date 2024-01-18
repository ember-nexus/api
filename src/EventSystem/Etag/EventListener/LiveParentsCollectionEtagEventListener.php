<?php

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\ParentsCollectionEtagEvent;
use App\Factory\Type\RedisKeyTypeFactory;
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

class LiveParentsCollectionEtagEventListener
{
    public const int REDIS_PARENTS_COLLECTION_TTL_IN_SECONDS = 3600;

    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private CypherEntityManager $cypherEntityManager,
        private RedisClient $redisClient,
        private RedisKeyTypeFactory $redisKeyTypeFactory,
        private LoggerInterface $logger
    ) {
    }

    public function onParentsCollectionEtagEvent(ParentsCollectionEtagEvent $event): void
    {
        $this->logger->debug(
            'Calculating Etag for parents collection.',
            [
                'childUuid' => $event->getChildUuid()->toString(),
            ]
        );
        $etag = $this->getParentsCollectionEtag($event->getChildUuid());
        $this->logger->debug(
            'Calculated Etag for parents collection.',
            [
                'childUuid' => $event->getChildUuid()->toString(),
                'etag' => $etag,
            ]
        );

        $redisKey = $this->redisKeyTypeFactory->getEtagParentsCollectionRedisKey($event->getChildUuid());

        $this->logger->debug(
            'Trying to persist Etag for parents collection in Redis.',
            [
                'childUuid' => $event->getChildUuid()->toString(),
                'redisKey' => $redisKey,
                'etag' => $etag,
            ]
        );

        $redisValue = $etag;
        if (null === $redisValue) {
            $redisValue = RedisValueType::NULL->value;
        }
        $this->redisClient->set($redisKey, $redisValue, 'EX', self::REDIS_PARENTS_COLLECTION_TTL_IN_SECONDS);

        $event->setEtag($etag);
        $event->stopPropagation();
    }

    private function getParentsCollectionEtag(UuidInterface $childUuid): ?string
    {
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        // todo: add relation id + updated to calculation
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH ({id: \$childUuid})<-[:OWNS]-(parents)\n".
                    "WITH parents\n".
                    "LIMIT %d\n".
                    "WITH parents ORDER BY parents.id\n".
                    "WITH [parents.id, parents.updated] AS parentsList\n".
                    'RETURN collect(parentsList) AS parentsList',
                    $limit + 1
                ),
                [
                    'childUuid' => $childUuid->toString(),
                ]
            )
        );
        if (1 !== count($result)) {
            throw new Exception('Unexpected result');
        }
        if (count($result[0]['parentsList']) > $limit) {
            return null;
        }

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        foreach ($result[0]['parentsList'] as $parentsIdUpdatedPair) {
            $parentId = Uuid::fromString($parentsIdUpdatedPair[0]);
            $parentUpdated = $parentsIdUpdatedPair[1];
            if (!($parentUpdated instanceof DateTimeZoneId)) {
                throw new Exception(sprintf('Expected variable element.updated to be of type %s, got %s.', DateTimeZoneId::class, get_class($parentUpdated)));
            }
            $etagCalculator->addUuid($parentId);
            $etagCalculator->addDateTime($parentUpdated->toDateTime());
        }

        return $etagCalculator->getEtag();
    }
}
