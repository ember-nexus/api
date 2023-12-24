<?php

namespace App\EventSystem\EntityManager\EventListener;

use App\EventSystem\EntityManager\Event\ElementPostDeleteEvent;
use App\EventSystem\EntityManager\Event\ElementPostMergeEvent;
use App\Type\RedisPrefixType;
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

    private function handleEvent(ElementPostMergeEvent|ElementPostDeleteEvent $event): void
    {
        $redisEtagKeysToExpire = [];

        $redisEtagKeysToExpire[] = sprintf(
            '%s%s',
            RedisPrefixType::ETAG->value,
            $event->getElement()->getIdentifier()->toString()
        );

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
                'elementId' => $event->getElement()->getIdentifier()->toString(),
            ]
        ));

        if (1 === count($result)) {
            foreach ($result[0]['childrenList'] as $childParentId) {
                print_r($childParentId);
                $redisEtagKeysToExpire[] = sprintf(
                    '%s%s/parents',
                    RedisPrefixType::ETAG->value,
                    Uuid::fromString($childParentId)->toString()
                );
            }
            foreach ($result[0]['parentsList'] as $parentChildId) {
                $redisEtagKeysToExpire[] = sprintf(
                    '%s%s/children',
                    RedisPrefixType::ETAG->value,
                    Uuid::fromString($parentChildId)->toString()
                );
            }
            foreach ($result[0]['relatedList'] as $relatedCenterId) {
                $redisEtagKeysToExpire[] = sprintf(
                    '%s%s/related',
                    RedisPrefixType::ETAG->value,
                    Uuid::fromString($relatedCenterId)->toString()
                );
            }
            foreach ($result[0]['indexList'] as $userId) {
                $redisEtagKeysToExpire[] = sprintf(
                    '%sindex-%s',
                    RedisPrefixType::ETAG->value,
                    Uuid::fromString($userId)->toString()
                );
            }
        }

        $result = $this->cypherEntityManager->getClient()->runStatement(Statement::create(
            "MATCH (start)-[relation {id: \$elementId}]->(end)\n".
                'RETURN start.id, type(relation) as type, end.id',
            [
                'elementId' => $event->getElement()->getIdentifier()->toString(),
            ]
        ));

        if (1 === count($result)) {
            $redisEtagKeysToExpire[] = sprintf(
                '%s%s/related',
                RedisPrefixType::ETAG->value,
                Uuid::fromString($result[0]['start.id'])->toString()
            );
            $redisEtagKeysToExpire[] = sprintf(
                '%s%s/related',
                RedisPrefixType::ETAG->value,
                Uuid::fromString($result[0]['end.id'])->toString()
            );
            if ('OWNS' === $result[0]['type']) {
                $redisEtagKeysToExpire[] = sprintf(
                    '%s%s/parents',
                    RedisPrefixType::ETAG->value,
                    Uuid::fromString($result[0]['end.id'])->toString()
                );
                $redisEtagKeysToExpire[] = sprintf(
                    '%s%s/children',
                    RedisPrefixType::ETAG->value,
                    Uuid::fromString($result[0]['start.id'])->toString()
                );
            }
        }

        foreach ($redisEtagKeysToExpire as $redisEtagKeyToExpire) {
            $this->redisClient->expire($redisEtagKeyToExpire, 0);
        }
    }
}
