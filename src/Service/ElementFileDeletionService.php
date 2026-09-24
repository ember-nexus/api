<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Contract\S3\FileOperationInterface;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * Removes the files of elements which are deleted, as the files live in S3 and are not removed with the element.
 *
 * Usage: collect the file operations before the element is deleted, as the relations attached to a node are gone
 * afterwards, then delete the files after the element was deleted and flushed. If deleting a file fails, the file
 * stays behind in S3 instead of the element pointing to a missing file.
 */
class ElementFileDeletionService
{
    public function __construct(
        private ElementManager $elementManager,
        private ElementService $elementService,
        private FileOperationFactory $fileOperationFactory,
        private S3Service $s3Service,
        private CypherEntityManager $cypherEntityManager,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
    ) {
    }

    /**
     * Includes the files of relations which are attached to the element, as they are deleted together with it.
     *
     * @return FileOperationInterface[]
     */
    public function getFileOperationsForDeletionOfElement(NodeElementInterface|RelationElementInterface $element): array
    {
        $elements = [$element];
        if ($element instanceof NodeElementInterface && null !== $element->getId()) {
            foreach ($this->getIdsOfAttachedRelationsWithFile($element->getId()->toString()) as $relationId) {
                $elements[] = $this->elementManager->getElementOrFail(UuidV4::fromString($relationId));
            }
        }

        $fileOperations = [];
        foreach ($elements as $elementWithPossibleFile) {
            if ($this->elementService->hasFile($elementWithPossibleFile)) {
                $fileOperations[] = $this->fileOperationFactory->createFileOperationFromElement($elementWithPossibleFile);
            }
        }

        return $fileOperations;
    }

    /**
     * @param FileOperationInterface[] $fileOperations
     */
    public function deleteFiles(array $fileOperations): void
    {
        foreach ($fileOperations as $fileOperation) {
            $this->s3Service->deleteFile($fileOperation);
        }
    }

    /**
     * @return string[]
     */
    private function getIdsOfAttachedRelationsWithFile(string $nodeId): array
    {
        $queryResult = $this->cypherEntityManager->getClient()->runStatement(new Statement(
            'MATCH ({id: $nodeId})-[r]-() WHERE r.hasFile = true RETURN DISTINCT r.id',
            [
                'nodeId' => $nodeId,
            ]
        ));

        $relationIds = [];
        foreach ($queryResult as $queryResultLine) {
            $relationId = $queryResultLine['r.id'];
            if (!is_string($relationId)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property r.id as string, not %s.', get_debug_type($relationId))); // @codeCoverageIgnore
            }
            $relationIds[] = $relationId;
        }

        return $relationIds;
    }
}
