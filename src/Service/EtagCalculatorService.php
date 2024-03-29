<?php

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
        private LoggerInterface $logger
    ) {
    }

    public function calculateElementEtag(UuidInterface $elementUuid): ?Etag
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
            return null;
        }

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        $etagCalculator->addUuid($elementUuid);
        $etagCalculator->addDateTime(DateTimeHelper::getDateTimeFromLaudisObject($updated));
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
        $this->logger->debug(
            'Calculating Etag for children collection.',
            [
                'parentUuid' => $parentUuid->toString(),
            ]
        );
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH (parent {id: \$parentUuid})\n".
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
                    'parentUuid' => $parentUuid->toString(),
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
                    'parentUuid' => $parentUuid->toString(),
                ]
            );

            return null;
        }

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        $etagCalculator->addUuid($parentUuid);
        foreach ($result[0]['sortedTuples'] as $idUpdatedPair) {
            $etagCalculator->addUuid(Uuid::fromString($idUpdatedPair[0]));
            $etagCalculator->addDateTime(DateTimeHelper::getDateTimeFromLaudisObject($idUpdatedPair[1]));
        }

        $etag = $etagCalculator->getEtag();

        $this->logger->debug(
            'Calculated Etag for children collection.',
            [
                'parentUuid' => $parentUuid->toString(),
                'etag' => $etag,
            ]
        );

        return $etag;
    }

    public function calculateParentsCollectionEtag(UuidInterface $childUuid): ?Etag
    {
        $this->logger->debug(
            'Calculating Etag for parents collection.',
            [
                'childUuid' => $childUuid->toString(),
            ]
        );
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH (child {id: \$childUuid})\n".
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
                    'childUuid' => $childUuid->toString(),
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
                    'childUuid' => $childUuid->toString(),
                ]
            );

            return null;
        }

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        $etagCalculator->addUuid($childUuid);
        foreach ($result[0]['sortedTuples'] as $idUpdatedPair) {
            $etagCalculator->addUuid(Uuid::fromString($idUpdatedPair[0]));
            $etagCalculator->addDateTime(DateTimeHelper::getDateTimeFromLaudisObject($idUpdatedPair[1]));
        }

        $etag = $etagCalculator->getEtag();

        $this->logger->debug(
            'Calculated Etag for parents collection.',
            [
                'childUuid' => $childUuid->toString(),
                'etag' => $etag,
            ]
        );

        return $etag;
    }

    public function calculateRelatedCollectionEtag(UuidInterface $centerUuid): ?Etag
    {
        $this->logger->debug(
            'Calculating Etag for related collection.',
            [
                'centerUuid' => $centerUuid->toString(),
            ]
        );
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH (center {id: \$centerUuid})\n".
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
                    'centerUuid' => $centerUuid->toString(),
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
                    'centerUuid' => $centerUuid->toString(),
                ]
            );

            return null;
        }

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        $etagCalculator->addUuid($centerUuid);
        foreach ($result[0]['sortedTuples'] as $idUpdatedPair) {
            $etagCalculator->addUuid(Uuid::fromString($idUpdatedPair[0]));
            $etagCalculator->addDateTime(DateTimeHelper::getDateTimeFromLaudisObject($idUpdatedPair[1]));
        }

        $etag = $etagCalculator->getEtag();

        $this->logger->debug(
            'Calculated Etag for related collection.',
            [
                'centerUuid' => $centerUuid->toString(),
                'etag' => $etag,
            ]
        );

        return $etag;
    }

    public function calculateIndexCollectionEtag(UuidInterface $userUuid): ?Etag
    {
        $this->logger->debug(
            'Calculating Etag for index collection.',
            [
                'userUuid' => $userUuid->toString(),
            ]
        );
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH (user:User {id: \$userUuid})\n".
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
                    'userUuid' => $userUuid->toString(),
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
                    'userUuid' => $userUuid->toString(),
                ]
            );

            return null;
        }

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        $etagCalculator->addUuid($userUuid);
        foreach ($result[0]['sortedTuples'] as $idUpdatedPair) {
            $etagCalculator->addUuid(Uuid::fromString($idUpdatedPair[0]));
            $etagCalculator->addDateTime(DateTimeHelper::getDateTimeFromLaudisObject($idUpdatedPair[1]));
        }

        $etag = $etagCalculator->getEtag();

        $this->logger->debug(
            'Calculated Etag for index collection.',
            [
                'userUuid' => $userUuid->toString(),
                'etag' => $etag,
            ]
        );

        return $etag;
    }
}
