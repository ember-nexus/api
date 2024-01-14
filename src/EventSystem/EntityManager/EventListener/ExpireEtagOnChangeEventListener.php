<?php

namespace App\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\Helper\RedisKeyHelper;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Predis\Client;
use Ramsey\Uuid\Uuid;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class ExpireEtagOnChangeEventListener
{
    public function __construct(
        private Client $redisClient,
        private CypherEntityManager $cypherEntityManager
    ) {
    }

    public function onElementPostMergeEvent(ElementPostMergeEvent $event): void
    {
        $this->handleEvent($event);
    }

    public function onElementPostDeleteEvent(ElementPostDeleteEvent $event): void
    {
        $this->handleEvent($event);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function handleEvent(ElementPostMergeEvent|ElementPostDeleteEvent $event): void
    {
        $elementUuid = $event->getElement()->getIdentifier();
        if (null === $elementUuid) {
            throw new Exception('Unable to expire etag for element with no identifier.');
        }

        $redisEtagKeysToExpire = [];

        $redisEtagKeysToExpire[] = RedisKeyHelper::getEtagElementRedisKey($elementUuid);

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
                'elementId' => $elementUuid->toString(),
            ]
        ));

        if (1 === count($result)) {
            foreach ($result[0]['childrenList'] as $childUuid) {
                $redisEtagKeysToExpire[] = RedisKeyHelper::getEtagParentsCollectionRedisKey(Uuid::fromString($childUuid));
            }
            foreach ($result[0]['parentsList'] as $parentUuid) {
                $redisEtagKeysToExpire[] = RedisKeyHelper::getEtagChildrenCollectionRedisKey(Uuid::fromString($parentUuid));
            }
            foreach ($result[0]['relatedList'] as $centerUuid) {
                $redisEtagKeysToExpire[] = RedisKeyHelper::getEtagRelatedCollectionRedisKey(Uuid::fromString($centerUuid));
            }
            foreach ($result[0]['indexList'] as $userUuid) {
                $redisEtagKeysToExpire[] = RedisKeyHelper::getEtagIndexCollectionRedisKey(Uuid::fromString($userUuid));
            }
        }

        $result = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (start)-[relation {id: \$elementId}]->(end)\n".
                'RETURN start.id, type(relation) as type, end.id',
            [
                'elementId' => $elementUuid->toString(),
            ]
        ));

        if (1 === count($result)) {
            $redisEtagKeysToExpire[] = RedisKeyHelper::getEtagRelatedCollectionRedisKey(Uuid::fromString($result[0]['start.id']));
            $redisEtagKeysToExpire[] = RedisKeyHelper::getEtagRelatedCollectionRedisKey(Uuid::fromString($result[0]['end.id']));
            if ('OWNS' === $result[0]['type']) {
                $redisEtagKeysToExpire[] = RedisKeyHelper::getEtagParentsCollectionRedisKey(Uuid::fromString($result[0]['end.id']));
                $redisEtagKeysToExpire[] = RedisKeyHelper::getEtagChildrenCollectionRedisKey(Uuid::fromString($result[0]['start.id']));
            }
        }

        foreach ($redisEtagKeysToExpire as $redisEtagKeyToExpire) {
            $this->redisClient->expire($redisEtagKeyToExpire, 0);
        }
    }
}
