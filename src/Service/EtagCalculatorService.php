<?php

declare(strict_types=1);

namespace App\Service;

use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Helper\CypherHelper;
use App\Helper\DateTimeHelper;
use App\Helper\ReadAccessCypherHelper;
use App\Type\Etag;
use App\Type\EtagCalculator;
use App\Type\FileHashAlgorithm;
use App\Type\Uuid;
use ArrayAccess;
use EmberNexusBundle\Service\EmberNexusConfiguration;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Laudis\Neo4j\Types\CypherList;
use Psr\Log\LoggerInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

class EtagCalculatorService
{
    public function __construct(
        private EmberNexusConfiguration $emberNexusConfiguration,
        private CypherEntityManager $cypherEntityManager,
        private ElementManager $elementManager,
        private ElementTypeService $elementTypeService,
        private LoggerInterface $logger,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    public function calculateElementEtag(Uuid $elementId): ?Etag
    {
        $this->logger->debug(
            'Calculating Etag for element.',
            [
                'elementId' => $elementId->toString(),
            ]
        );

        $resolved = $this->elementTypeService->resolve($elementId);
        if (null === $resolved) {
            return null;
        }
        $escapedType = CypherHelper::escapeIdentifier($resolved->getType());
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                $resolved->isNode()
                    ? sprintf('MATCH (element:%s {id: $elementId}) RETURN element.updated AS updated', $escapedType)
                    : sprintf('MATCH ()-[element:%s {id: $elementId}]->() RETURN element.updated AS updated', $escapedType),
                [
                    'elementId' => $elementId->toString(),
                ]
            )
        );
        $updated = $result[0]['updated'] ?? null;
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

    public function calculateChildrenCollectionEtag(Uuid $parentId): ?Etag
    {
        $this->logger->debug(
            'Calculating Etag for children collection.',
            [
                'parentId' => $parentId->toString(),
            ]
        );
        $label = $this->elementTypeService->resolveNodeLabel($parentId);
        if (null === $label) {
            return null;
        }
        $escapedLabel = CypherHelper::escapeIdentifier($label);
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH (parent:%s {id: \$parentId})\n".
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
                    $escapedLabel,
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
        $rawSortedTuples = $result[0]['sortedTuples'];
        if (!($rawSortedTuples instanceof CypherList)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property sortedTuples as array, not %s.', get_debug_type($rawSortedTuples))); // @codeCoverageIgnore
        }
        $this->addSortedTuples($etagCalculator, $rawSortedTuples);

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

    public function calculateParentsCollectionEtag(Uuid $childId): ?Etag
    {
        $this->logger->debug(
            'Calculating Etag for parents collection.',
            [
                'childId' => $childId->toString(),
            ]
        );
        $label = $this->elementTypeService->resolveNodeLabel($childId);
        if (null === $label) {
            return null;
        }
        $escapedLabel = CypherHelper::escapeIdentifier($label);
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH (child:%s {id: \$childId})\n".
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
                    $escapedLabel,
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
        $rawSortedTuples = $result[0]['sortedTuples'];
        if (!($rawSortedTuples instanceof CypherList)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property sortedTuples as array, not %s.', get_debug_type($rawSortedTuples))); // @codeCoverageIgnore
        }
        $this->addSortedTuples($etagCalculator, $rawSortedTuples);

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

    public function calculateRelatedCollectionEtag(Uuid $centerId): ?Etag
    {
        $this->logger->debug(
            'Calculating Etag for related collection.',
            [
                'centerId' => $centerId->toString(),
            ]
        );
        $label = $this->elementTypeService->resolveNodeLabel($centerId);
        if (null === $label) {
            return null;
        }
        $escapedLabel = CypherHelper::escapeIdentifier($label);
        $limit = $this->emberNexusConfiguration->getCacheEtagUpperLimitInCollectionEndpoints();
        $result = $this->cypherEntityManager->getClient()->runStatement(
            Statement::create(
                sprintf(
                    "MATCH (center:%s {id: \$centerId})\n".
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
                    $escapedLabel,
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
        $rawSortedTuples = $result[0]['sortedTuples'];
        if (!($rawSortedTuples instanceof CypherList)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property sortedTuples as array, not %s.', get_debug_type($rawSortedTuples))); // @codeCoverageIgnore
        }
        $this->addSortedTuples($etagCalculator, $rawSortedTuples);

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

    public function calculateIndexCollectionEtag(Uuid $userId): ?Etag
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
                    "MATCH (user)-[relation:OWNS|IS_IN_GROUP|HAS_READ_ACCESS]->(elements)\n".
                    'WHERE '.ReadAccessCypherHelper::directReadRelationCondition('relation', 'elements').
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
        $rawSortedTuples = $result[0]['sortedTuples'];
        if (!($rawSortedTuples instanceof CypherList)) {
            throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property sortedTuples as array, not %s.', get_debug_type($rawSortedTuples))); // @codeCoverageIgnore
        }
        $this->addSortedTuples($etagCalculator, $rawSortedTuples);

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

    public function calculateFileEtag(Uuid $elementId): ?Etag
    {
        $this->logger->debug(
            'Calculating Etag for file.',
            [
                'elementId' => $elementId->toString(),
            ]
        );

        $element = $this->elementManager->getElementOrFail($elementId);
        if (!$element->hasProperty('hasFile') || true !== $element->getProperty('hasFile')) {
            // an element without file has no file representation, so conditional requests on it are not evaluated
            return null;
        }

        $elementEtag = $this->calculateElementEtag($elementId);
        if (null === $elementEtag) {
            return null;
        }

        $fileHash = $this->extractPreferredHashFromFileProperties($element->hasProperty('file') ? $element->getProperty('file') : null);

        $etagCalculator = new EtagCalculator($this->emberNexusConfiguration->getCacheEtagSeed());
        $etagCalculator->addString((string) $elementEtag);
        if (null !== $fileHash) {
            $etagCalculator->addString($fileHash);
        }
        // no stored hash, e.g. files uploaded before hashing was introduced: the element etag already captures
        // identity and recency, so it is not folded into itself again as a fake hash component
        $etag = $etagCalculator->getEtag();

        $this->logger->debug(
            'Calculated Etag for file.',
            [
                'elementId' => $elementId->toString(),
                'etag' => $etag,
            ]
        );

        return $etag;
    }

    /**
     * @param CypherList<mixed> $sortedTuples list of [id, updated] pairs
     */
    private function addSortedTuples(EtagCalculator $etagCalculator, CypherList $sortedTuples): void
    {
        /** @var CypherList<mixed> $idUpdatedPair */
        foreach ($sortedTuples as $idUpdatedPair) {
            $rawId = $idUpdatedPair[0];
            if (!is_string($rawId)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return sortedTuples ids as string, not %s.', get_debug_type($rawId))); // @codeCoverageIgnore
            }
            $etagCalculator->addUuid(Uuid::fromString($rawId));
            $etagCalculator->addDateTime(DateTimeHelper::getDateTimeFromLaudisObject($idUpdatedPair[1]));
        }
    }

    /**
     * Prefers `sha256`, otherwise uses the alphabetically first algorithm for determinism.
     *
     * @psalm-suppress MixedAssignment
     */
    private function extractPreferredHashFromFileProperties(mixed $rawFileProperties): ?string
    {
        if (!is_array($rawFileProperties) && !($rawFileProperties instanceof ArrayAccess)) {
            return null;
        }
        $hash = $rawFileProperties['hash'] ?? null;
        if (is_object($hash) && method_exists($hash, 'getArrayCopy')) {
            $hash = $hash->getArrayCopy();
        }
        if (!is_array($hash) || [] === $hash) {
            return null;
        }

        $preferredValue = $hash[FileHashAlgorithm::SHA_256->value] ?? null;
        if (is_string($preferredValue) && '' !== $preferredValue) {
            return $preferredValue;
        }

        ksort($hash);
        foreach ($hash as $value) {
            if (is_string($value) && '' !== $value) {
                return $value;
            }
        }

        return null;
    }
}
