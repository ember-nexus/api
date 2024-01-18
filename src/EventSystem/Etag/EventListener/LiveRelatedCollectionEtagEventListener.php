<?php

namespace App\EventSystem\Etag\EventListener;

use App\EventSystem\Etag\Event\RelatedCollectionEtagEvent;
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

class LiveRelatedCollectionEtagEventListener
{
    public const int REDIS_RELATED_COLLECTION_TTL_IN_SECONDS = 3600;

    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private CypherEntityManager $cypherEntityManager,
        private RedisClient $redisClient,
        private RedisKeyTypeFactory $redisKeyTypeFactory,
        private LoggerInterface $logger
    ) {
    }

    public function onRelatedCollectionEtagEvent(RelatedCollectionEtagEvent $event): void
    {
        $this->logger->debug(
            'Calculating Etag for related collection.',
            [
                'centerUuid' => $event->getCenterUuid()->toString(),
            ]
        );
        $etag = $this->getRelatedCollectionEtag($event->getCenterUuid());
        $this->logger->debug(
            'Calculated Etag for related collection.',
            [
                'centerUuid' => $event->getCenterUuid()->toString(),
                'etag' => $etag,
            ]
        );

        $redisKey = $this->redisKeyTypeFactory->getEtagRelatedCollectionRedisKey($event->getCenterUuid());

        $this->logger->debug(
            'Trying to persist Etag for related collection in Redis.',
            [
                'centerUuid' => $event->getCenterUuid()->toString(),
                'redisKey' => $redisKey,
                'etag' => $etag,
            ]
        );

        $redisValue = $etag;
        if (null === $redisValue) {
            $redisValue = RedisValueType::NULL->value;
        }
        $this->redisClient->set($redisKey, $redisValue, 'EX', self::REDIS_RELATED_COLLECTION_TTL_IN_SECONDS);

        $event->setEtag($etag);
        $event->stopPropagation();
    }

    private function getRelatedCollectionEtag(UuidInterface $centerUuid): ?string
    {
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        // todo: add relation id + updated to calculation
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH ({id: \$centerUuid})-[]-(related)\n".
                    "WITH related\n".
                    "LIMIT %d\n".
                    "WITH related ORDER BY related.id\n".
                    "WITH [related.id, related.updated] AS relatedList\n".
                    'RETURN collect(relatedList) AS relatedList',
                    $limit + 1
                ),
                [
                    'centerUuid' => $centerUuid->toString(),
                ]
            )
        );
        if (1 !== count($result)) {
            throw new Exception('Unexpected result');
        }
        if (count($result[0]['relatedList']) > $limit) {
            return null;
        }

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        foreach ($result[0]['relatedList'] as $relatedIdUpdatedPair) {
            $relatedId = Uuid::fromString($relatedIdUpdatedPair[0]);
            $relatedUpdated = $relatedIdUpdatedPair[1];
            if (!($relatedUpdated instanceof DateTimeZoneId)) {
                throw new Exception(sprintf('Expected variable element.updated to be of type %s, got %s.', DateTimeZoneId::class, get_class($relatedUpdated)));
            }
            $etagCalculator->addUuid($relatedId);
            $etagCalculator->addDateTime($relatedUpdated->toDateTime());
        }

        return $etagCalculator->getEtag();
    }
}
