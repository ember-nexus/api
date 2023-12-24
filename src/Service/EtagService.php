<?php

namespace App\Service;

use App\Type\Etag;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\DateTimeZoneId;
use Predis\Client as RedisClient;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class EtagService
{
    public function __construct(
        private CypherEntityManager $cypherEntityManager,
        private RedisClient $redisClient,
        private EmberNexusConfiguration $emberNexusConfiguration
    ) {
    }

    public function getEtagForChildrenCollection(UuidInterface $parentId): ?string
    {
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH ({id: \$parentId})-[:OWNS]->(children)\n".
                    "WITH children\n".
                    "LIMIT %d\n".
                    "WITH children ORDER BY children.id\n".
                    "WITH [children.id, children.updated] AS childrenList\n".
                    'RETURN collect(childrenList) AS childrenList',
                    $limit + 1
                ),
                [
                    'parentId' => $parentId->toString(),
                ]
            )
        );
        if (1 !== count($result)) {
            throw new Exception('Unexpected result');
        }
        if (count($result[0]['childrenList']) > $limit) {
            return null;
        }

        $etag = new Etag($this->emberNexusConfiguration->getCacheEtagSeed());
        foreach ($result[0]['childrenList'] as $childIdUpdatedPair) {
            $childId = Uuid::fromString($childIdUpdatedPair[0]);
            $childUpdated = $childIdUpdatedPair[1];
            if (!($childUpdated instanceof DateTimeZoneId)) {
                throw new Exception(sprintf('Expected variable element.updated to be of type %s, got %s.', DateTimeZoneId::class, get_class($childUpdated)));
            }
            $etag->addUuid($childId);
            $etag->addDatetime($childUpdated->toDateTime());
        }

        return $etag->getEtag();
    }

    public function getEtagForElementId(UuidInterface $elementId): string
    {
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                "OPTIONAL MATCH (node {id: \$elementId})\n".
                "OPTIONAL MATCH ()-[relation {id: \$elementId}]->()\n".
                'RETURN node.updated, relation.updated',
                [
                    'elementId' => $elementId->toString(),
                ]
            )
        );
        $updated = $result[0]['node.updated'] ?? $result[0]['relation.updated'] ?? null;
        if (null === $updated) {
            throw new Exception(sprintf('Unable to find node or relation with id %s.', $elementId->toString()));
        }
        if (!($updated instanceof DateTimeZoneId)) {
            throw new Exception(sprintf('Expected variable element.updated to be of type %s, got %s.', DateTimeZoneId::class, get_class($updated)));
        }

        $etag = new Etag($this->emberNexusConfiguration->getCacheEtagSeed());
        $etag->addUuid($elementId);
        $etag->addDatetime($updated->toDateTime());

        return $etag->getEtag();
    }
}
