<?php

declare(strict_types=1);

namespace App\Service;

use App\Helper\DateTimeHelper;
use App\Type\Etag;
use App\Type\EtagCalculator;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class EtagCalculatorService
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private CypherEntityManager $cypherEntityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function calculateElementEtag(UuidInterface $elementId): ?Etag
    {
        $this->logger->debug(
            'Calculating Etag for element.',
            [
                'elementId' => $elementId->toString(),
            ]
        );

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
            return null;
        }

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        $etagCalculator->addUuid($elementId);
        $etagCalculator->addDateTime(DateTimeHelper::getDateTimeFromLaudisObject($updated));
        $etag = $etagCalculator->getEtag();

        $this->logger->debug(
            'Calculated Etag for element.',
            [
                'elementId' => $elementId->toString(),
                'etag' => $etag,
            ]
        );

        return $etag;
    }

    public function calculateChildrenCollectionEtag(UuidInterface $parentId): ?Etag
    {
        $this->logger->debug(
            'Calculating Etag for children collection.',
            [
                'parentId' => $parentId->toString(),
            ]
        );
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH (parent {id: \$parentId})\n".
                    "MATCH (parent)-[:OWNS]->(children)\n".
                    "MATCH (parent)-[relations]->(children)\n".
                    "WITH children, relations\n".
                    "LIMIT %d\n".
                    "WITH children, relations\n".
                    "ORDER BY children.id, relations.id\n".
                    "WITH COLLECT([children.id, children.updated]) + COLLECT([relations.id, relations.updated]) AS rawTuples, count(children) as childrenCount\n".
                    "CALL {\n".
                    "  WITH rawTuples\n".
                    "  UNWIND rawTuples as tuple\n".
                    "  WITH tuple ORDER BY tuple[0]\n".
                    "  RETURN COLLECT(tuple) AS sortedTuples\n".
                    "}\n".
                    'RETURN sortedTuples, childrenCount',
                    $limit + 1
                ),
                [
                    'parentId' => $parentId->toString(),
                ]
            )
        );
        if (1 !== count($result)) {
            throw new Exception('Unexpected result.');
        }
        if ($result[0]['childrenCount'] > $limit) {
            $this->logger->debug(
                'Calculation of Etag for children collection stopped due to too many children.',
                [
                    'parentId' => $parentId->toString(),
                ]
            );

            return null;
        }

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        $etagCalculator->addUuid($parentId);
        foreach ($result[0]['sortedTuples'] as $idUpdatedPair) {
            $etagCalculator->addUuid(Uuid::fromString($idUpdatedPair[0]));
            $etagCalculator->addDateTime(DateTimeHelper::getDateTimeFromLaudisObject($idUpdatedPair[1]));
        }

        $etag = $etagCalculator->getEtag();

        $this->logger->debug(
            'Calculated Etag for children collection.',
            [
                'parentId' => $parentId->toString(),
                'etag' => $etag,
            ]
        );

        return $etag;
    }

    public function calculateParentsCollectionEtag(UuidInterface $childId): ?Etag
    {
        $this->logger->debug(
            'Calculating Etag for parents collection.',
            [
                'childId' => $childId->toString(),
            ]
        );
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH (child {id: \$childId})\n".
                    "MATCH (child)<-[:OWNS]-(parents)\n".
                    "MATCH (child)<-[relations]-(parents)\n".
                    "WITH parents, relations\n".
                    "LIMIT %d\n".
                    "WITH parents, relations\n".
                    "ORDER BY parents.id, relations.id\n".
                    "WITH COLLECT([parents.id, parents.updated]) + COLLECT([relations.id, relations.updated]) AS rawTuples, count(parents) as parentsCount\n".
                    "CALL {\n".
                    "  WITH rawTuples\n".
                    "  UNWIND rawTuples as tuple\n".
                    "  WITH tuple ORDER BY tuple[0]\n".
                    "  RETURN COLLECT(tuple) AS sortedTuples\n".
                    "}\n".
                    'RETURN sortedTuples, parentsCount',
                    $limit + 1
                ),
                [
                    'childId' => $childId->toString(),
                ]
            )
        );
        if (1 !== count($result)) {
            throw new Exception('Unexpected result.');
        }
        if ($result[0]['parentsCount'] > $limit) {
            $this->logger->debug(
                'Calculation of Etag for parents collection stopped due to too many parents.',
                [
                    'childId' => $childId->toString(),
                ]
            );

            return null;
        }

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        $etagCalculator->addUuid($childId);
        foreach ($result[0]['sortedTuples'] as $idUpdatedPair) {
            $etagCalculator->addUuid(Uuid::fromString($idUpdatedPair[0]));
            $etagCalculator->addDateTime(DateTimeHelper::getDateTimeFromLaudisObject($idUpdatedPair[1]));
        }

        $etag = $etagCalculator->getEtag();

        $this->logger->debug(
            'Calculated Etag for parents collection.',
            [
                'childId' => $childId->toString(),
                'etag' => $etag,
            ]
        );

        return $etag;
    }

    public function calculateRelatedCollectionEtag(UuidInterface $centerId): ?Etag
    {
        $this->logger->debug(
            'Calculating Etag for related collection.',
            [
                'centerId' => $centerId->toString(),
            ]
        );
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH (center {id: \$centerId})\n".
                    "MATCH (center)-[relations]-(related)\n".
                    "WITH related, relations\n".
                    "LIMIT %d\n".
                    "WITH related, relations\n".
                    "ORDER BY related.id, relations.id\n".
                    "WITH COLLECT([related.id, related.updated]) + COLLECT([relations.id, relations.updated]) AS rawTuples, count(related) as relatedCount\n".
                    "CALL {\n".
                    "  WITH rawTuples\n".
                    "  UNWIND rawTuples as tuple\n".
                    "  WITH tuple ORDER BY tuple[0]\n".
                    "  RETURN COLLECT(tuple) AS sortedTuples\n".
                    "}\n".
                    'RETURN sortedTuples, relatedCount',
                    $limit + 1
                ),
                [
                    'centerId' => $centerId->toString(),
                ]
            )
        );
        if (1 !== count($result)) {
            throw new Exception('Unexpected result.');
        }
        if ($result[0]['relatedCount'] > $limit) {
            $this->logger->debug(
                'Calculation of Etag for related collection stopped due to too many related elements.',
                [
                    'centerId' => $centerId->toString(),
                ]
            );

            return null;
        }

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        $etagCalculator->addUuid($centerId);
        foreach ($result[0]['sortedTuples'] as $idUpdatedPair) {
            $etagCalculator->addUuid(Uuid::fromString($idUpdatedPair[0]));
            $etagCalculator->addDateTime(DateTimeHelper::getDateTimeFromLaudisObject($idUpdatedPair[1]));
        }

        $etag = $etagCalculator->getEtag();

        $this->logger->debug(
            'Calculated Etag for related collection.',
            [
                'centerId' => $centerId->toString(),
                'etag' => $etag,
            ]
        );

        return $etag;
    }

    public function calculateIndexCollectionEtag(UuidInterface $userId): ?Etag
    {
        $this->logger->debug(
            'Calculating Etag for index collection.',
            [
                'userId' => $userId->toString(),
            ]
        );
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH (user:User {id: \$userId})\n".
                    "MATCH (user)-[:OWNS|IS_IN_GROUP|HAS_READ_ACCESS]->(elements)\n".
                    "WITH elements\n".
                    "LIMIT %d\n".
                    "WITH elements\n".
                    "ORDER BY elements.id\n".
                    "WITH COLLECT([elements.id, elements.updated]) AS rawTuples, count(elements) as elementsCount\n".
                    "CALL {\n".
                    "  WITH rawTuples\n".
                    "  UNWIND rawTuples as tuple\n".
                    "  WITH tuple ORDER BY tuple[0]\n".
                    "  RETURN COLLECT(tuple) AS sortedTuples\n".
                    "}\n".
                    'RETURN sortedTuples, elementsCount',
                    $limit + 1
                ),
                [
                    'userId' => $userId->toString(),
                ]
            )
        );
        if (1 !== count($result)) {
            throw new Exception('Unexpected result.');
        }
        if ($result[0]['elementsCount'] > $limit) {
            $this->logger->debug(
                'Calculation of Etag for index collection stopped due to too many index elements.',
                [
                    'userId' => $userId->toString(),
                ]
            );

            return null;
        }

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        $etagCalculator->addUuid($userId);
        foreach ($result[0]['sortedTuples'] as $idUpdatedPair) {
            $etagCalculator->addUuid(Uuid::fromString($idUpdatedPair[0]));
            $etagCalculator->addDateTime(DateTimeHelper::getDateTimeFromLaudisObject($idUpdatedPair[1]));
        }

        $etag = $etagCalculator->getEtag();

        $this->logger->debug(
            'Calculated Etag for index collection.',
            [
                'userId' => $userId->toString(),
                'etag' => $etag,
            ]
        );

        return $etag;
    }
}
