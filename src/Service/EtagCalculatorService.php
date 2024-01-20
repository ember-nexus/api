<?php

namespace App\Service;

use App\Type\Etag;
use App\Type\EtagCalculator;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\DateTimeZoneId;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class EtagCalculatorService
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private CypherEntityManager $cypherEntityManager,
        private LoggerInterface $logger
    ) {
    }

    public function calculateElementEtag(UuidInterface $elementUuid): Etag
    {
        $this->logger->debug(
            'Calculating Etag for element.',
            [
                'elementUuid' => $elementUuid->toString(),
            ]
        );

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
        $etag = $etagCalculator->getEtag();

        $this->logger->debug(
            'Calculated Etag for element.',
            [
                'elementUuid' => $elementUuid->toString(),
                'etag' => $etag,
            ]
        );

        return $etag;
    }

    public function calculateChildrenCollectionEtag(UuidInterface $parentUuid): ?Etag
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

    public function calculateIndexCollectionEtag(UuidInterface $userUuid): ?Etag
    {
        // todo: implement
        return null;
    }

    public function calculateParentsCollectionEtag(UuidInterface $childUuid): ?Etag
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

    public function calculateRelatedCollectionEtag(UuidInterface $centerUuid): ?Etag
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
