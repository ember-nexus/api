<?php

declare(strict_types=1);

namespace App\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPostCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\EventSystem\EntityManager\Event\ElementPreDeleteEvent;
use App\Factory\Type\RedisKeyFactory;
use App\Type\RedisKey;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Predis\Client;
use Ramsey\Uuid\Uuid;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class ExpireEtagOnChangeEventListener
{
    public function __construct(
        private Client $redisClient,
        private CypherEntityManager $cypherEntityManager,
        private RedisKeyFactory $redisKeyTypeFactory
    ) {
    }

    public function onElementPostCreateEvent(ElementPostCreateEvent $event): void
    {
        $this->handleEvent($event);
    }

    public function onElementPostMergeEvent(ElementPostMergeEvent $event): void
    {
        $this->handleEvent($event);
    }

    public function onElementPreDeleteEvent(ElementPreDeleteEvent $event): void
    {
        $this->handleEvent($event);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function handleEvent(ElementPostCreateEvent|ElementPostMergeEvent|ElementPreDeleteEvent $event): void
    {
        $elementId = $event->getElement()->getId();
        if (null === $elementId) {
            throw new Exception('Unable to expire etag for element with no identifier.');
        }

        $redisEtagKeysToExpire = [];
        /**
         * @var RedisKey[] $redisEtagKeysToExpire
         */
        $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagElementRedisKey($elementId);

        $result = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (node {id: \$elementId})\n".
                "RETURN COLLECT {\n".
                "    MATCH (node)-[:OWNS]->(children)\n".
                "    RETURN children.id\n".
                "} as childrenList, COLLECT {\n".
                "    MATCH (parents)-[:OWNS]->(node)\n".
                "    RETURN parents.id\n".
                "} as parentsList, COLLECT {\n".
                "    MATCH (node)-[]-(related)\n".
                "    RETURN related.id\n".
                "} as relatedList, COLLECT {\n".
                "    MATCH (user:User)-[:IS_IN_GROUP*0..]->()-[:OWNS]->(node)\n".
                "    RETURN user.id\n".
                '} as indexList',
            [
                'elementId' => $elementId->toString(),
            ]
        ));

        if (1 === count($result)) {
            foreach ($result[0]['childrenList'] as $childId) {
                $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagParentsCollectionRedisKey(Uuid::fromString($childId));
            }
            foreach ($result[0]['parentsList'] as $parentId) {
                $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagChildrenCollectionRedisKey(Uuid::fromString($parentId));
            }
            foreach ($result[0]['relatedList'] as $centerId) {
                $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagRelatedCollectionRedisKey(Uuid::fromString($centerId));
            }
            foreach ($result[0]['indexList'] as $userId) {
                $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagIndexCollectionRedisKey(Uuid::fromString($userId));
            }
        }

        $result = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (start)-[relation {id: \$elementId}]->(end)\n".
                'RETURN start.id, type(relation) as type, end.id',
            [
                'elementId' => $elementId->toString(),
            ]
        ));

        if (1 === count($result)) {
            $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagRelatedCollectionRedisKey(Uuid::fromString($result[0]['start.id']));
            $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagRelatedCollectionRedisKey(Uuid::fromString($result[0]['end.id']));
            if ('OWNS' === $result[0]['type']) {
                $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagParentsCollectionRedisKey(Uuid::fromString($result[0]['end.id']));
                $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagChildrenCollectionRedisKey(Uuid::fromString($result[0]['start.id']));
            }
        }

        foreach ($redisEtagKeysToExpire as $redisEtagKeyToExpire) {
            $this->redisClient->expire((string) $redisEtagKeyToExpire, 0);
        }
    }
}
