<?php

declare(strict_types=1);

namespace App\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPostCreateEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\EventSystem\EntityManager\Event\ElementPreDeleteEvent;
use App\Factory\Exception\Server500LogicExceptionFactory;
use App\Factory\Type\RedisKeyFactory;
use App\Type\RedisKey;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\CypherList;
use Predis\Client;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class ExpireEtagOnChangeEventListener
{
    public function __construct(
        private Client $redisClient,
        private CypherEntityManager $cypherEntityManager,
        private RedisKeyFactory $redisKeyTypeFactory,
        private Server500LogicExceptionFactory $server500LogicExceptionFactory,
    ) {
    }

    #[AsEventListener]
    public function onElementPostCreateEvent(ElementPostCreateEvent $event): void
    {
        $this->handleEvent($event);
    }

    #[AsEventListener]
    public function onElementPostMergeEvent(ElementPostMergeEvent $event): void
    {
        $this->handleEvent($event);
    }

    #[AsEventListener]
    public function onElementPreDeleteEvent(ElementPreDeleteEvent $event): void
    {
        $this->handleEvent($event);
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
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
            $rawChildrenList = $result[0]['childrenList'];
            if (!($rawChildrenList instanceof CypherList)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property childrenList as CypherList, not %s.', get_debug_type($rawChildrenList))); // @codeCoverageIgnore
            }
            foreach ($rawChildrenList as $childId) {
                if (!is_string($childId)) {
                    throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property childrenList.item as string, not %s.', get_debug_type($childId))); // @codeCoverageIgnore
                }
                $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagParentsCollectionRedisKey(Uuid::fromString($childId));
            }
            $rawParentsList = $result[0]['parentsList'];
            if (!($rawParentsList instanceof CypherList)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property parentsList as CypherList, not %s.', get_debug_type($rawParentsList))); // @codeCoverageIgnore
            }
            foreach ($rawParentsList as $parentId) {
                if (!is_string($parentId)) {
                    throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property parentsList.item as string, not %s.', get_debug_type($parentId))); // @codeCoverageIgnore
                }
                $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagChildrenCollectionRedisKey(Uuid::fromString($parentId));
            }
            $rawRelatedList = $result[0]['relatedList'];
            if (!($rawRelatedList instanceof CypherList)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property relatedList as CypherList, not %s.', get_debug_type($rawRelatedList))); // @codeCoverageIgnore
            }
            foreach ($rawRelatedList as $centerId) {
                if (!is_string($centerId)) {
                    throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property relatedList.item as string, not %s.', get_debug_type($centerId))); // @codeCoverageIgnore
                }
                $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagRelatedCollectionRedisKey(Uuid::fromString($centerId));
            }
            $rawIndexList = $result[0]['indexList'];
            if (!($rawIndexList instanceof CypherList)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property indexList as CypherList, not %s.', get_debug_type($rawIndexList))); // @codeCoverageIgnore
            }
            foreach ($rawIndexList as $userId) {
                if (!is_string($userId)) {
                    throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property indexList.item as string, not %s.', get_debug_type($userId))); // @codeCoverageIgnore
                }
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
            $rawStartId = $result[0]['start.id'];
            if (!is_string($rawStartId)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property start.id as string, not %s.', get_debug_type($rawStartId))); // @codeCoverageIgnore
            }
            $rawEndId = $result[0]['end.id'];
            if (!is_string($rawEndId)) {
                throw $this->server500LogicExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property end.id as string, not %s.', get_debug_type($rawEndId))); // @codeCoverageIgnore
            }
            $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagRelatedCollectionRedisKey(Uuid::fromString($rawStartId));
            $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagRelatedCollectionRedisKey(Uuid::fromString($rawEndId));
            if ('OWNS' === $result[0]['type']) {
                $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagParentsCollectionRedisKey(Uuid::fromString($rawEndId));
                $redisEtagKeysToExpire[] = $this->redisKeyTypeFactory->getEtagChildrenCollectionRedisKey(Uuid::fromString($rawStartId));
            }
        }

        foreach ($redisEtagKeysToExpire as $redisEtagKeyToExpire) {
            $this->redisClient->expire((string) $redisEtagKeyToExpire, 0);
        }
    }
}
