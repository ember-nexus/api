<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\RelationElementInterface;
use App\Contract\S3\FileOperationInterface;
use App\Contract\S3\MergeFileChunksOperationInterface;
use App\Contract\S3\UploadFileOperationInterface;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\S3OperationFactory;
use App\Type\S3\FileOperation;
use Laudis\Neo4j\Databags\Statement;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;
use Throwable;

/**
 * Removes the files of elements which are deleted, as the files live in S3 and are not removed with the element.
 *
 * Usage: collect the file operations before the element is deleted, as the relations attached to a node are gone
 * afterwards, then delete the files after the element was deleted and flushed. If deleting a file fails, the failure
 * is logged and the file stays behind in S3 as an orphan instead of the element pointing to a missing file.
 */
class ElementFileDeletionService
{
    public function __construct(
        private ElementManager $elementManager,
        private ElementService $elementService,
        private S3OperationFactory $s3OperationFactory,
        private S3Service $s3Service,
        private CypherEntityManager $cypherEntityManager,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
        private LoggerInterface $logger,
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
                $fileOperations[] = $this->s3OperationFactory->createFileOperationFromElement($elementWithPossibleFile);
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
            $this->deleteFile($fileOperation);
        }
    }

    /**
     * Deletes the object a replaced file was stored in before, if the new file is stored under a different key (e.g.
     * changed extension). To be called after the element was flushed and therefore points to the new object; a
     * failing delete is logged and never fails the request. Overwrites of the same key can not be rolled back.
     */
    public function deletePreviousFileAfterReplace(UploadFileOperationInterface|MergeFileChunksOperationInterface $operation): void
    {
        $previousStorageKey = $operation->getPreviousStorageKey();
        if (null === $previousStorageKey || $previousStorageKey === $operation->getStorageKey()) {
            return;
        }
        $this->deleteFile(new FileOperation($operation->getStorageBucket(), $previousStorageKey));
    }

    /**
     * Never throws: the element (or its file reference) is already gone, so a failing S3 delete must not fail the
     * request. The remaining object is an orphan.
     */
    public function deleteFile(FileOperationInterface $fileOperation): void
    {
        try {
            $this->s3Service->deleteFile($fileOperation);
        } catch (Throwable $throwable) {
            $this->logger->error(
                sprintf(
                    "Unable to delete file '%s' from bucket '%s', the object stays behind as an orphan: %s",
                    $fileOperation->getKey(),
                    $fileOperation->getBucket(),
                    $throwable->getMessage()
                ),
                ['bucket' => $fileOperation->getBucket(), 'key' => $fileOperation->getKey()]
            );
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
