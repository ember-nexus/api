<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\NodeElementInterface;
use App\Contract\UploadInterface;
use App\Factory\Exception\Server500LogicErrorExceptionFactory;
use App\Factory\Type\S3\FileOperationFactory;
use App\Factory\Type\UploadFactory;
use App\Type\NodeElement;
use Exception;
use Laudis\Neo4j\Databags\Statement;
use Ramsey\Uuid\Rfc4122\UuidV4;
use Ramsey\Uuid\UuidInterface;
use Syndesi\CypherEntityManager\Type\EntityManager as CypherEntityManager;

/**
 * @SuppressWarnings("PHPMD.ExcessiveParameterList")
 */
class UploadService
{
    public function __construct(
        private ElementManager $elementManager,
        private Server500LogicErrorExceptionFactory $server500LogicErrorExceptionFactory,
        private FileOperationFactory $fileOperationFactory,
        private S3Service $s3Service,
        private UploadFactory $uploadFactory,
        private CypherEntityManager $cypherEntityManager,
    ) {
    }

    public function mergeUploadElement(UploadInterface $upload): void
    {
        $element = $this->elementManager->getElement($upload->getId());
        if (null !== $element) {
            if (!($element instanceof NodeElementInterface)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected upload element to be a node, received %s.', get_debug_type($element)));
            }
            if ('Upload' !== $element->getLabel()) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf("Expected upload element to be of type 'Upload', not '%s'.", $element->getLabel() ?? 'null'));
            }
        } else {
            $element = (new NodeElement())
                ->setId($upload->getId())
                ->setLabel('Upload');
        }

        $element->addProperty('uploadLength', $upload->getUploadLength());
        $element->addProperty('uploadOffset', $upload->getUploadOffset());
        $element->addProperty('uploadComplete', $upload->isUploadComplete());
        $element->addProperty('uploadTarget', $upload->getUploadTarget()->toString());
        // the list itself is stored in MongoDB, only the last id is a graph property, see appendChunkIfOffsetMatches()
        $element->addProperty('chunkIds', $upload->getChunkIds());
        $element->addProperty('lastChunkId', $upload->getLastChunkId());
        $element->addProperty('uploadOwner', $upload->getUploadOwner()->toString());
        $element->addProperty('extension', $upload->getExtension());
        $element->addProperty('expires', $upload->getExpires());
        $element->addProperty('hashState', $upload->getHashState());

        $this->elementManager->merge($element);
    }

    /**
     * Compare-and-set on `uploadOffset` and `lastChunkId`, the only graph properties of the chunk list (the list
     * itself is stored in MongoDB, and an empty final chunk does not move the offset): appends the chunk of $next
     * only if the stored upload is still incomplete and in the state of $expected, i.e. no other request appended
     * a chunk in the meantime. Chunk ids are unique per attempt, so `lastChunkId` identifies the state exactly.
     * The graph update is atomic, the list is written by the following merge, which is flushed. Returns false if the
     * upload was modified concurrently.
     */
    public function appendChunkIfOffsetMatches(UploadInterface $expected, UploadInterface $next): bool
    {
        $queryResult = $this->cypherEntityManager->getClient()->runStatement(new Statement(
            'MATCH (u:Upload {id: $id}) WHERE u.uploadOffset = $expectedOffset AND coalesce(u.lastChunkId, "") = $expectedLastChunkId AND u.uploadComplete = false '.
            'SET u.uploadOffset = $uploadOffset, u.uploadComplete = $uploadComplete, u.lastChunkId = $lastChunkId, u.hashState = $hashState '.
            'RETURN u.id',
            [
                'id' => $expected->getId()->toString(),
                'expectedOffset' => $expected->getUploadOffset(),
                'expectedLastChunkId' => $expected->getLastChunkId() ?? '',
                'uploadOffset' => $next->getUploadOffset(),
                'uploadComplete' => $next->isUploadComplete(),
                'lastChunkId' => $next->getLastChunkId(),
                'hashState' => $next->getHashState(),
            ]
        ));
        if (0 === $queryResult->count()) {
            return false;
        }

        $this->mergeUploadElement($next);
        $this->elementManager->flush();

        return true;
    }

    public function deleteUpload(UploadInterface $upload): void
    {
        $element = $this->elementManager->getElementOrFail($upload->getId());

        $this->elementManager->delete($element);
    }

    /**
     * Does not flush, same as {@see deleteUpload()}.
     */
    public function deleteUploadAndChunks(UploadInterface $upload): void
    {
        // chunk keys start at 1; rejected chunk attempts are not part of the upload and are deleted by their request
        foreach ($upload->getChunkIds() as $index => $chunkId) {
            $deleteChunkOperation = $this->fileOperationFactory->createFileOperationFromUpload($upload, $index + 1, $chunkId);
            $this->s3Service->deleteFile($deleteChunkOperation);
        }

        $this->deleteUpload($upload);
    }

    /**
     * Deletes uploads targeting the element, as `uploadTarget` is a plain property and not a relation. Must be
     * called and flushed before the element itself is deleted; it is not an event listener because nested
     * `flush()` calls are not supported.
     */
    public function deleteUploadsTargeting(UuidInterface $elementId): void
    {
        foreach ($this->getUploadIdsTargeting($elementId) as $uploadId) {
            $uploadElement = $this->elementManager->getElement(UuidV4::fromString($uploadId));
            if (null === $uploadElement) {
                continue;
            }

            try {
                $upload = $this->uploadFactory->createUploadFromElement($uploadElement);
            } catch (Exception) {
                continue; // @codeCoverageIgnore
            }

            $this->deleteUploadAndChunks($upload);
        }
    }

    /**
     * @return string[]
     */
    private function getUploadIdsTargeting(UuidInterface $elementId): array
    {
        $queryResult = $this->cypherEntityManager->getClient()->runStatement(new Statement(
            'MATCH (u:Upload) WHERE u.uploadTarget = $elementId RETURN u.id',
            [
                'elementId' => $elementId->toString(),
            ]
        ));

        $uploadIds = [];
        foreach ($queryResult as $queryResultLine) {
            $uploadId = $queryResultLine['u.id'];
            if (!is_string($uploadId)) {
                throw $this->server500LogicErrorExceptionFactory->createFromTemplate(sprintf('Expected cypher response to return property u.id as string, not %s.', get_debug_type($uploadId))); // @codeCoverageIgnore
            }
            $uploadIds[] = $uploadId;
        }

        return $uploadIds;
    }
}
